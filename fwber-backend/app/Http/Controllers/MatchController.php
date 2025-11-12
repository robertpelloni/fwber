<?php

namespace App\Http\Controllers;

use App\Http\Resources\MatchResource;
use App\Models\User;
use App\Models\UserProfile;
use App\Models\ProximityArtifact;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class MatchController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = auth()->user();
        $profile = $user->profile;
        
        if (!$profile) {
            return response()->json([
                'message' => 'Profile not found. Please complete your profile first.',
            ], 400);
        }

        // Validate filter parameters
        $request->validate([
            'age_min' => 'nullable|integer|min:18|max:100',
            'age_max' => 'nullable|integer|min:18|max:100',
            'max_distance' => 'nullable|integer|min:1|max:500',
        ]);

        // Cache feed for 60 seconds per user with filter params
        $cacheKey = "feed:user_{$user->id}:" . md5($request->getQueryString() ?? '');
        
        $matches = Cache::remember($cacheKey, 60, function () use ($user, $profile, $request) {
            return $this->findMatches($user, $profile, $request);
        });

        // Emit telemetry
        app(\App\Services\TelemetryService::class)->emit('feed.viewed', [
            'user_id' => $user->id,
            'count' => $matches->count(),
            'filters_applied' => !empty($request->query()),
        ]);

        return response()->json([
            "matches" => MatchResource::collection($matches)->toArray(request()),
            "total" => $matches->count(),
        ]);
    }

    public function action(Request $request): JsonResponse
    {
        $request->validate([
            'action' => 'required|in:like,pass,super_like',
            'target_user_id' => 'required|integer|exists:users,id',
        ]);

        $user = auth()->user();
        $targetUserId = $request->target_user_id;
        $action = $request->action;

        // Prevent self-action
        if ($user->id === $targetUserId) {
            return response()->json(['message' => 'Cannot perform action on yourself'], 400);
        }

        // Check if target user is accessible
        if (!$this->isUserAccessible($user, $targetUserId)) {
            return response()->json(['message' => 'User not accessible'], 400);
        }

        // Record the action
        $this->recordMatchAction($user->id, $targetUserId, $action);

        // Check for mutual match
        $isMatch = $this->checkForMatch($user->id, $targetUserId);

        return response()->json([
            'action' => $action,
            'is_match' => $isMatch,
            'message' => $isMatch ? 'It\'s a match!' : 'Action recorded',
        ]);
    }

    private function findMatches(User $user, UserProfile $profile, Request $request)
    {
        $query = User::query()
            ->whereKeyNot($user->id)
            ->whereHas('profile')
            ->with(['profile']);

        // Distance filter
        if ($profile->location_latitude && $profile->location_longitude) {
            $maxDistance = $request->get('max_distance', 50); // Default 50 miles
            $query->whereHas('profile', function ($q) use ($profile, $maxDistance) {
                $latDist = (1.1 * $maxDistance) / 49.1;
                $lonDist = (1.1 * $maxDistance) / 69.1;
                
                $q->whereBetween('location_latitude', [
                    $profile->location_latitude - $latDist,
                    $profile->location_latitude + $latDist
                ])->whereBetween('location_longitude', [
                    $profile->location_longitude - $lonDist,
                    $profile->location_longitude + $lonDist
                ]);
            });
        }

        // Gender preference filter
        if ($profile->preferences && isset($profile->preferences['gender_preferences'])) {
            $genderPrefs = $profile->preferences['gender_preferences'];
            $query->whereHas('profile', function ($q) use ($genderPrefs) {
                $q->whereIn('gender', array_keys(array_filter($genderPrefs)));
            });
        }

        // Age filter (request params override preferences)
        $ageMin = (int) $request->get('age_min', $profile->preferences['age_range']['min'] ?? 18);
        $ageMax = (int) $request->get('age_max', $profile->preferences['age_range']['max'] ?? 100);
        
        $query->whereHas('profile', function ($q) use ($ageMin, $ageMax) {
            // SQLite-compatible age calculation
            $q->whereRaw("(julianday('now') - julianday(date_of_birth)) / 365.25 BETWEEN ? AND ?", [
                $ageMin,
                $ageMax
            ]);
        });

        // Exclude users already interacted with
        $excludedIds = DB::table('match_actions')
            ->where('user_id', $user->id)
            ->pluck('target_user_id')
            ->toArray();
        
        if (!empty($excludedIds)) {
            $query->whereNotIn('id', $excludedIds);
        }

        $matches = $query->limit(20)->get();

        // Calculate compatibility scores and sort
        return $matches->map(function (User $candidate) use ($user, $profile) {
            $candidate->setAttribute(
                'compatibility_score',
                $this->calculateCompatibilityScore($profile, $candidate->profile)
            );
            $candidate->setAttribute(
                'distance',
                $this->calculateDistance($profile, $candidate->profile)
            );
            return $candidate;
        })->sortByDesc('compatibility_score')->values();
    }

    private function calculateCompatibilityScore(UserProfile $userProfile, UserProfile $candidateProfile): int
    {
        $score = 0;
        $maxScore = 100;

        // Location compatibility (20 points)
        if ($userProfile->location_latitude && $candidateProfile->location_latitude) {
            $distance = $this->calculateDistance($userProfile, $candidateProfile);
            $score += max(0, 20 - ($distance / 5)); // Decrease by 1 point per 5 miles
        }

        // Age compatibility (15 points)
        if ($userProfile->date_of_birth && $candidateProfile->date_of_birth) {
            $userAge = $userProfile->date_of_birth->diffInYears(now());
            $candidateAge = $candidateProfile->date_of_birth->diffInYears(now());
            $ageDiff = abs($userAge - $candidateAge);
            $score += max(0, 15 - $ageDiff);
        }

        // Gender compatibility (25 points)
        if ($this->checkGenderCompatibility($userProfile, $candidateProfile)) {
            $score += 25;
        }

        // Preference compatibility (40 points)
        $score += $this->calculatePreferenceCompatibility($userProfile, $candidateProfile);

        // Freshness/recency boost (up to 5 points)
        $score += $this->calculateFreshnessBoost($candidateProfile->user);

        // Saturation penalty for heavy proximity posting (up to -5 points)
        $score -= $this->calculateProximitySaturationPenalty($candidateProfile->user);

        return min($maxScore, max(0, $score));
    }

    private function calculateFreshnessBoost(User $candidate): int
    {
        if (!$candidate->last_seen_at) {
            return 0;
        }

        $hoursSinceActive = now()->diffInHours($candidate->last_seen_at);
        
        if ($hoursSinceActive < 1) {
            return 5; // Very recent activity
        } elseif ($hoursSinceActive < 24) {
            return 3; // Active today
        } elseif ($hoursSinceActive < 168) {
            return 1; // Active this week
        }
        
        return 0;
    }

    private function calculateProximitySaturationPenalty(User $candidate): int
    {
        // Count artifacts created by candidate in last 24 hours
        $count = ProximityArtifact::where('user_id', $candidate->id)
            ->where('created_at', '>=', now()->subDay())
            ->count();
        // Each 10 artifacts -> 1 point penalty, capped at 5
        return (int) min(5, floor($count / 10));
    }

    private function calculateDistance(UserProfile $profile1, UserProfile $profile2): float
    {
        if (!$profile1->location_latitude || !$profile2->location_latitude) {
            return 0;
        }

        $lat1 = $profile1->location_latitude;
        $lon1 = $profile1->location_longitude;
        $lat2 = $profile2->location_latitude;
        $lon2 = $profile2->location_longitude;

        $theta = $lon1 - $lon2;
        $dist = sin(deg2rad($lat1)) * sin(deg2rad($lat2)) + 
                cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * cos(deg2rad($theta));
        $dist = acos($dist);
        $dist = rad2deg($dist);
        $miles = $dist * 60 * 1.1515;

        return $miles;
    }

    private function checkGenderCompatibility(UserProfile $userProfile, UserProfile $candidateProfile): bool
    {
        if (!$userProfile->preferences || !$candidateProfile->preferences) {
            return true; // Default to compatible if no preferences set
        }

        $userGenderPrefs = $userProfile->preferences['gender_preferences'] ?? [];
        $candidateGenderPrefs = $candidateProfile->preferences['gender_preferences'] ?? [];

        // Check if user wants candidate's gender
        $userWantsCandidate = isset($userGenderPrefs[$candidateProfile->gender]) && 
                             $userGenderPrefs[$candidateProfile->gender];

        // Check if candidate wants user's gender
        $candidateWantsUser = isset($candidateGenderPrefs[$userProfile->gender]) && 
                             $candidateGenderPrefs[$userProfile->gender];

        return $userWantsCandidate && $candidateWantsUser;
    }

    private function calculatePreferenceCompatibility(UserProfile $userProfile, UserProfile $candidateProfile): int
    {
        $score = 0;
        $maxScore = 40;

        if (!$userProfile->preferences || !$candidateProfile->preferences) {
            return $maxScore / 2; // Default score if no preferences
        }

        // Compare various preferences
        $preferenceKeys = ['relationship_style', 'sexual_orientation', 'sti_status'];
        
        foreach ($preferenceKeys as $key) {
            if (isset($userProfile->preferences[$key]) && isset($candidateProfile->preferences[$key])) {
                if ($userProfile->preferences[$key] === $candidateProfile->preferences[$key]) {
                    $score += $maxScore / count($preferenceKeys);
                }
            }
        }

        return $score;
    }

    private function isUserAccessible(User $user, int $targetUserId): bool
    {
        $userProfile = $user->profile;
        $targetUser = User::with('profile')->find($targetUserId);
        
        if (!$userProfile || !$targetUser || !$targetUser->profile) {
            return false;
        }

        // Check distance
        $distance = $this->calculateDistance($userProfile, $targetUser->profile);
        $maxDistance = $userProfile->preferences['max_distance'] ?? 50;
        
        if ($distance > $maxDistance) {
            return false;
        }

        // Check gender compatibility
        return $this->checkGenderCompatibility($userProfile, $targetUser->profile);
    }

    private function recordMatchAction(int $userId, int $targetUserId, string $action): void
    {
        DB::table('match_actions')->insert([
            'user_id' => $userId,
            'target_user_id' => $targetUserId,
            'action' => $action,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function checkForMatch(int $userId, int $targetUserId): bool
    {
        $mutualLike = DB::table('match_actions')
            ->where('user_id', $targetUserId)
            ->where('target_user_id', $userId)
            ->where('action', 'like')
            ->exists();

        if ($mutualLike) {
            $user1 = min($userId, $targetUserId);
            $user2 = max($userId, $targetUserId);

            // Create match record (idempotent: skip if exists)
            $existing = DB::table('matches')
                ->where('user1_id', $user1)
                ->where('user2_id', $user2)
                ->exists();

            if (!$existing) {
                DB::table('matches')->insert([
                    'user1_id' => $user1,
                    'user2_id' => $user2,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                // Send email notifications to both users
                $emailService = app(\App\Services\EmailNotificationService::class);
                $userA = User::find($user1);
                $userB = User::find($user2);
                
                if ($userA && $userB) {
                    $emailService->sendNewMatchNotification($userA, $userB);
                    $emailService->sendNewMatchNotification($userB, $userA);
                }
            }

            // Auto chat creation under feature flag
            $flags = app(\App\Services\FeatureFlagService::class);
            if ($flags->isEnabled('auto_chat_on_match')) {
                $this->createAutoChatIfMissing($user1, $user2);
            }
        }

        return $mutualLike;
    }

    /**
     * Create a private chatroom for a matched pair if one doesn't exist; insert system message.
     */
    private function createAutoChatIfMissing(int $user1, int $user2): void
    {
        // Deterministic unique name for pair
        $pairName = "match_{$user1}_{$user2}";

        // Look for existing private chatroom with both members
        $existing = \App\Models\Chatroom::query()
            ->where('type', 'private')
            ->where('name', $pairName)
            ->first();

        if (!$existing) {
            $chatroom = \App\Models\Chatroom::create([
                'name' => $pairName,
                'description' => 'Private chat for matched users',
                'type' => 'private',
                'created_by' => $user1,
                'is_public' => false,
                'is_active' => true,
                'member_count' => 0,
                'message_count' => 0,
            ]);

            // Attach members
            $chatroom->addMember(\App\Models\User::find($user1));
            $chatroom->addMember(\App\Models\User::find($user2));

            // System message
            \App\Models\ChatroomMessage::create([
                'chatroom_id' => $chatroom->id,
                'user_id' => $user1, // attribute to first user for simplicity
                'content' => "It's a match! Start your conversation.",
                'type' => 'system',
                'is_edited' => false,
                'is_deleted' => false,
            ]);

            $chatroom->update(['message_count' => 1, 'last_activity_at' => now()]);
        }
    }
}
