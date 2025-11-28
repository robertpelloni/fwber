<?php

namespace App\Http\Controllers;

use App\Http\Resources\MatchResource;
use App\Models\User;
use App\Models\UserProfile;
use App\Models\ProximityArtifact;
use App\Services\AIMatchingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use App\Services\PushNotificationService;

class MatchController extends Controller
{
    private AIMatchingService $matchingService;

    public function __construct(AIMatchingService $matchingService)
    {
        $this->matchingService = $matchingService;
    }
    /**
     * @OA\Get(
     *     path="/matches",
     *     tags={"Matches"},
     *     summary="Get potential matches",
     *     description="Retrieve a feed of potential matches based on user preferences, location, and filters. Results are cached for 60 seconds per user.",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="age_min",
     *         in="query",
     *         description="Minimum age filter",
     *         required=false,
     *         @OA\Schema(type="integer", minimum=18, maximum=100, example=25)
     *     ),
     *     @OA\Parameter(
     *         name="age_max",
     *         in="query",
     *         description="Maximum age filter",
     *         required=false,
     *         @OA\Schema(type="integer", minimum=18, maximum=100, example=40)
     *     ),
     *     @OA\Parameter(
     *         name="max_distance",
     *         in="query",
     *         description="Maximum distance in miles",
     *         required=false,
     *         @OA\Schema(type="integer", minimum=1, maximum=500, default=50, example=25)
     *     ),
     *     @OA\Parameter(
     *         name="smoking",
     *         in="query",
     *         description="Filter by smoking preference",
     *         required=false,
     *         @OA\Schema(type="string", enum={"non-smoker", "occasional", "regular", "social", "trying-to-quit"})
     *     ),
     *     @OA\Parameter(
     *         name="drinking",
     *         in="query",
     *         description="Filter by drinking preference",
     *         required=false,
     *         @OA\Schema(type="string", enum={"non-drinker", "occasional", "regular", "social", "sober"})
     *     ),
     *     @OA\Parameter(
     *         name="body_type",
     *         in="query",
     *         description="Filter by body type",
     *         required=false,
     *         @OA\Schema(type="string", enum={"slim", "athletic", "average", "curvy", "plus-size", "muscular"})
     *     ),
     *     @OA\Parameter(
     *         name="height_min",
     *         in="query",
     *         description="Minimum height in centimeters",
     *         required=false,
     *         @OA\Schema(type="integer", minimum=120, maximum=250)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Matches retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(
     *                 property="matches",
     *                 type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="id", type="integer", example=42),
     *                     @OA\Property(property="name", type="string", example="Jane Smith"),
     *                     @OA\Property(property="age", type="integer", example=28),
     *                     @OA\Property(property="bio", type="string", example="Love hiking and coffee"),
     *                     @OA\Property(property="distance", type="number", format="float", example=5.3),
     *                     @OA\Property(property="match_score", type="integer", example=85),
     *                     @OA\Property(property="avatar_url", type="string", nullable=true)
     *                 )
     *             ),
     *             @OA\Property(property="total", type="integer", example=15)
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Profile not complete",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Profile not found. Please complete your profile first.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated",
     *         @OA\JsonContent(ref="#/components/schemas/UnauthorizedError")
     *     )
     * )
     */
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
            'smoking' => 'nullable|string|in:non-smoker,occasional,regular,social,trying-to-quit',
            'drinking' => 'nullable|string|in:non-drinker,occasional,regular,social,sober',
            'body_type' => 'nullable|string|in:slim,athletic,average,curvy,plus-size,muscular',
            'height_min' => 'nullable|integer|min:120|max:250',
        ]);

        // Cache feed for 5 minutes per user with filter params
        $cacheKey = "feed:user_{$user->id}:" . md5($request->getQueryString() ?? '');
        
        $matches = Cache::tags(["matches_feed:user_{$user->id}"])->remember($cacheKey, 300, function () use ($user, $profile, $request) {
            $filters = [
                'age_min' => $request->get('age_min'),
                'age_max' => $request->get('age_max'),
                'max_distance' => $request->get('max_distance'),
            ];

            $candidates = $this->matchingService->findAdvancedMatches($user, $filters);
            
            // Convert array back to collection and map attributes for resource
            return collect($candidates)->map(function ($candidate) use ($profile) {
                // Map ai_score to compatibility_score for the resource
                $candidate->setAttribute('compatibility_score', $candidate->ai_score);
                
                // Calculate distance for display (service uses it for scoring but doesn't return it)
                $candidate->setAttribute(
                    'distance',
                    $this->calculateDistance($profile, $candidate->profile)
                );
                
                return $candidate;
            });
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

    /**
     * @OA\Get(
     *     path="/matches/established",
     *     tags={"Matches"},
     *     summary="Get established matches",
     *     description="Retrieve a list of users the authenticated user has matched with.",
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Matches retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="array", @OA\Items(type="object"))
     *         )
     *     )
     * )
     */
    public function establishedMatches(Request $request): JsonResponse
    {
        $user = auth()->user();
        
        $cacheKey = "matches:established:user_{$user->id}";

        $conversations = Cache::tags(["matches_list:user_{$user->id}"])->remember($cacheKey, 60, function () use ($user) {
            $matches = DB::table('matches')
                ->where('user1_id', $user->id)
                ->orWhere('user2_id', $user->id)
                ->get();

            $userIds = $matches->map(function ($match) use ($user) {
                return $match->user1_id === $user->id ? $match->user2_id : $match->user1_id;
            });

            $users = User::with(['profile', 'photos'])->whereIn('id', $userIds)->get();

            // Format as "Conversation" objects for frontend compatibility
            return $users->map(function ($otherUser) use ($user, $matches) {
                // Find the match record
                $match = $matches->first(function ($m) use ($user, $otherUser) {
                    return ($m->user1_id === $user->id && $m->user2_id === $otherUser->id) ||
                           ($m->user1_id === $otherUser->id && $m->user2_id === $user->id);
                });

                // Get last message
                $lastMessage = \App\Models\Message::where(function ($q) use ($user, $otherUser) {
                    $q->where('sender_id', $user->id)->where('receiver_id', $otherUser->id);
                })->orWhere(function ($q) use ($user, $otherUser) {
                    $q->where('sender_id', $otherUser->id)->where('receiver_id', $user->id);
                })->latest()->first();

                return [
                    'id' => $match->id, // Match ID acts as conversation ID
                    'user1_id' => $match->user1_id,
                    'user2_id' => $match->user2_id,
                    'created_at' => $match->created_at,
                    'updated_at' => $match->updated_at,
                    'last_message' => $lastMessage,
                    'other_user' => $otherUser,
                ];
            });
        });

        return response()->json(['data' => $conversations]);
    }

    /**
     * @OA\Post(
     *     path="/matches/action",
     *     tags={"Matches"},
     *     summary="Perform match action",
     *     description="Like, pass, or super like a potential match. Returns whether action resulted in a mutual match.",
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"action", "target_user_id"},
     *             @OA\Property(property="action", type="string", enum={"like", "pass", "super_like"}, example="like"),
     *             @OA\Property(property="target_user_id", type="integer", example=42)
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Action recorded successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="action", type="string", example="like"),
     *             @OA\Property(property="is_match", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="It's a match!")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Invalid action or user not accessible",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Cannot perform action on yourself")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated",
     *         @OA\JsonContent(ref="#/components/schemas/UnauthorizedError")
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(ref="#/components/schemas/ValidationError")
     *     )
     * )
     */
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

        // Invalidate feed cache so the user doesn't see this person again immediately
        Cache::tags(["matches_feed:user_{$user->id}"])->flush();

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
            // Database-agnostic age calculation
            $startDate = now()->subYears($ageMax + 1)->addDay();
            $endDate = now()->subYears($ageMin);
            $q->whereBetween('date_of_birth', [$startDate, $endDate]);
        });

        // Advanced filters
        if ($request->has('smoking')) {
            $query->whereHas('profile', fn ($q) => $q->where('preferences->smoking', $request->smoking));
        }
        if ($request->has('drinking')) {
            $query->whereHas('profile', fn ($q) => $q->where('preferences->drinking', $request->drinking));
        }
        if ($request->has('body_type')) {
            $query->whereHas('profile', fn ($q) => $q->where('preferences->body_type', $request->body_type));
        }
        if ($request->has('height_min')) {
            $query->whereHas('profile', fn ($q) => $q->where('height_cm', '>=', $request->height_min));
        }

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

                // Invalidate established matches cache for both users
                Cache::tags(["matches_list:user_{$user1}"])->flush();
                Cache::tags(["matches_list:user_{$user2}"])->flush();

                // Send email notifications to both users
                $emailService = app(\App\Services\EmailNotificationService::class);
                $userA = User::find($user1);
                $userB = User::find($user2);
                
                if ($userA && $userB) {
                    $emailService->sendNewMatchNotification($userA, $userB);
                    $emailService->sendNewMatchNotification($userB, $userA);

                    // Send push notifications to both users
                    $pushService = app(PushNotificationService::class);
                    $pushService->send($userA, [
                        'title' => 'It\'s a match!',
                        'body' => "You've matched with {$userB->name}!",
                    ]);
                    $pushService->send($userB, [
                        'title' => 'It\'s a match!',
                        'body' => "You've matched with {$userA->name}!",
                    ]);
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
