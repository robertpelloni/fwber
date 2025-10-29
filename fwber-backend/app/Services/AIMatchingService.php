<?php

namespace App\Services;

use App\Models\User;
use App\Models\UserProfile;
use App\Models\MatchAction;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class AIMatchingService
{
    private $behavioralWeights = [
        'profile_view' => 0.1,
        'like' => 0.3,
        'super_like' => 0.5,
        'message_sent' => 0.4,
        'match' => 0.6,
    ];

    public function findAdvancedMatches(User $user, int $limit = 20): array
    {
        $cacheKey = "ai_matches_{$user->id}";
        
        return Cache::remember($cacheKey, 300, function () use ($user, $limit) {
            $userProfile = $user->profile;
            if (!$userProfile) {
                return [];
            }

            // Get behavioral preferences
            $behavioralPrefs = $this->analyzeUserBehavior($user);
            
            // Get candidates with basic filters
            $candidates = $this->getCandidates($user, $userProfile);
            
            // Score each candidate
            $scoredCandidates = $candidates->map(function ($candidate) use ($user, $userProfile, $behavioralPrefs) {
                $score = $this->calculateAdvancedScore($user, $userProfile, $candidate, $behavioralPrefs);
                $candidate->ai_score = $score;
                return $candidate;
            });

            // Sort by AI score and return top matches
            return $scoredCandidates
                ->sortByDesc('ai_score')
                ->take($limit)
                ->values()
                ->toArray();
        });
    }

    private function analyzeUserBehavior(User $user): array
    {
        // Analyze user's interaction patterns
        $interactions = MatchAction::where('user_id', $user->id)
            ->select('target_user_id', 'action', DB::raw('COUNT(*) as count'))
            ->groupBy('target_user_id', 'action')
            ->get();

        $behavioralPrefs = [
            'preferred_ages' => [],
            'preferred_locations' => [],
            'preferred_genders' => [],
            'interaction_patterns' => [],
        ];

        foreach ($interactions as $interaction) {
            $targetUser = User::with('profile')->find($interaction->target_user_id);
            if (!$targetUser || !$targetUser->profile) {
                continue;
            }

            $weight = $this->behavioralWeights[$interaction->action] ?? 0.1;
            $count = $interaction->count;

            // Analyze age preferences
            if ($targetUser->profile->date_of_birth) {
                $age = $targetUser->profile->date_of_birth->diffInYears(now());
                $behavioralPrefs['preferred_ages'][$age] = 
                    ($behavioralPrefs['preferred_ages'][$age] ?? 0) + ($weight * $count);
            }

            // Analyze location preferences
            if ($targetUser->profile->location_description) {
                $location = $targetUser->profile->location_description;
                $behavioralPrefs['preferred_locations'][$location] = 
                    ($behavioralPrefs['preferred_locations'][$location] ?? 0) + ($weight * $count);
            }

            // Analyze gender preferences
            if ($targetUser->profile->gender) {
                $gender = $targetUser->profile->gender;
                $behavioralPrefs['preferred_genders'][$gender] = 
                    ($behavioralPrefs['preferred_genders'][$gender] ?? 0) + ($weight * $count);
            }
        }

        return $behavioralPrefs;
    }

    private function getCandidates(User $user, UserProfile $userProfile)
    {
        $query = User::query()
            ->whereKeyNot($user->id)
            ->whereHas('profile')
            ->with(['profile']);

        // Basic distance filter
        if ($userProfile->location_latitude && $userProfile->location_longitude) {
            $maxDistance = 50; // Default 50 miles
            $query->whereHas('profile', function ($q) use ($userProfile, $maxDistance) {
                $latDist = (1.1 * $maxDistance) / 49.1;
                $lonDist = (1.1 * $maxDistance) / 69.1;
                
                $q->whereBetween('location_latitude', [
                    $userProfile->location_latitude - $latDist,
                    $userProfile->location_latitude + $latDist
                ])->whereBetween('location_longitude', [
                    $userProfile->location_longitude - $lonDist,
                    $userProfile->location_longitude + $lonDist
                ]);
            });
        }

        // Exclude users already interacted with
        $excludedIds = MatchAction::where('user_id', $user->id)
            ->pluck('target_user_id')
            ->toArray();
        
        if (!empty($excludedIds)) {
            $query->whereNotIn('id', $excludedIds);
        }

        return $query->limit(100)->get(); // Get more candidates for AI scoring
    }

    private function calculateAdvancedScore(User $user, UserProfile $userProfile, User $candidate, array $behavioralPrefs): float
    {
        $score = 0;
        $candidateProfile = $candidate->profile;

        if (!$candidateProfile) {
            return 0;
        }

        // Base compatibility score (40%)
        $baseScore = $this->calculateBaseCompatibility($userProfile, $candidateProfile);
        $score += $baseScore * 0.4;

        // Behavioral matching score (30%)
        $behavioralScore = $this->calculateBehavioralScore($candidateProfile, $behavioralPrefs);
        $score += $behavioralScore * 0.3;

        // Communication style similarity (20%)
        $communicationScore = $this->calculateCommunicationScore($userProfile, $candidateProfile);
        $score += $communicationScore * 0.2;

        // Mutual interest score (10%)
        $mutualScore = $this->calculateMutualInterestScore($user, $candidate);
        $score += $mutualScore * 0.1;

        return min(100, max(0, $score));
    }

    private function calculateBaseCompatibility(UserProfile $userProfile, UserProfile $candidateProfile): float
    {
        $score = 0;

        // Age compatibility
        if ($userProfile->date_of_birth && $candidateProfile->date_of_birth) {
            $userAge = $userProfile->date_of_birth->diffInYears(now());
            $candidateAge = $candidateProfile->date_of_birth->diffInYears(now());
            $ageDiff = abs($userAge - $candidateAge);
            $score += max(0, 20 - $ageDiff);
        }

        // Distance compatibility
        if ($userProfile->location_latitude && $candidateProfile->location_latitude) {
            $distance = $this->calculateDistance($userProfile, $candidateProfile);
            $score += max(0, 20 - ($distance / 5));
        }

        // Gender compatibility
        if ($this->checkGenderCompatibility($userProfile, $candidateProfile)) {
            $score += 25;
        }

        // Preference compatibility
        $score += $this->calculatePreferenceCompatibility($userProfile, $candidateProfile);

        return $score;
    }

    private function calculateBehavioralScore(UserProfile $candidateProfile, array $behavioralPrefs): float
    {
        $score = 0;

        // Age preference matching
        if ($candidateProfile->date_of_birth) {
            $age = $candidateProfile->date_of_birth->diffInYears(now());
            $ageScore = $behavioralPrefs['preferred_ages'][$age] ?? 0;
            $score += min(25, $ageScore);
        }

        // Location preference matching
        if ($candidateProfile->location_description) {
            $locationScore = $behavioralPrefs['preferred_locations'][$candidateProfile->location_description] ?? 0;
            $score += min(25, $locationScore);
        }

        // Gender preference matching
        if ($candidateProfile->gender) {
            $genderScore = $behavioralPrefs['preferred_genders'][$candidateProfile->gender] ?? 0;
            $score += min(25, $genderScore);
        }

        return $score;
    }

    private function calculateCommunicationScore(UserProfile $userProfile, UserProfile $candidateProfile): float
    {
        $score = 0;

        // Bio similarity analysis
        if ($userProfile->bio && $candidateProfile->bio) {
            $userBio = strtolower($userProfile->bio);
            $candidateBio = strtolower($candidateProfile->bio);
            
            // Simple keyword matching (in a real implementation, use NLP)
            $userWords = array_unique(str_word_count($userBio, 1));
            $candidateWords = array_unique(str_word_count($candidateBio, 1));
            
            $commonWords = array_intersect($userWords, $candidateWords);
            $totalWords = count($userWords) + count($candidateWords);
            
            if ($totalWords > 0) {
                $similarity = (count($commonWords) * 2) / $totalWords;
                $score += $similarity * 50;
            }
        }

        // Bio length compatibility
        $userBioLength = strlen($userProfile->bio ?? '');
        $candidateBioLength = strlen($candidateProfile->bio ?? '');
        
        if ($userBioLength > 0 && $candidateBioLength > 0) {
            $lengthDiff = abs($userBioLength - $candidateBioLength);
            $maxLength = max($userBioLength, $candidateBioLength);
            $lengthScore = 1 - ($lengthDiff / $maxLength);
            $score += $lengthScore * 25;
        }

        return $score;
    }

    private function calculateMutualInterestScore(User $user, User $candidate): float
    {
        // Check if candidate has shown interest in user
        $mutualInterest = MatchAction::where('user_id', $candidate->id)
            ->where('target_user_id', $user->id)
            ->whereIn('action', ['like', 'super_like'])
            ->exists();

        return $mutualInterest ? 50 : 0;
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
            return true;
        }

        $userGenderPrefs = $userProfile->preferences['gender_preferences'] ?? [];
        $candidateGenderPrefs = $candidateProfile->preferences['gender_preferences'] ?? [];

        $userWantsCandidate = isset($userGenderPrefs[$candidateProfile->gender]) && 
                             $userGenderPrefs[$candidateProfile->gender];
        $candidateWantsUser = isset($candidateGenderPrefs[$userProfile->gender]) && 
                             $candidateGenderPrefs[$userProfile->gender];

        return $userWantsCandidate && $candidateWantsUser;
    }

    private function calculatePreferenceCompatibility(UserProfile $userProfile, UserProfile $candidateProfile): float
    {
        $score = 0;

        if (!$userProfile->preferences || !$candidateProfile->preferences) {
            return 25; // Default score
        }

        $preferenceKeys = ['relationship_style', 'sexual_orientation', 'sti_status'];
        
        foreach ($preferenceKeys as $key) {
            if (isset($userProfile->preferences[$key]) && isset($candidateProfile->preferences[$key])) {
                if ($userProfile->preferences[$key] === $candidateProfile->preferences[$key]) {
                    $score += 25 / count($preferenceKeys);
                }
            }
        }

        return $score;
    }

    public function updateBehavioralModel(): void
    {
        // This method would be called by a scheduled task to retrain the behavioral model
        Log::info('Updating behavioral matching model');
        
        // In a real implementation, this would:
        // 1. Collect all user interaction data
        // 2. Train/retrain the machine learning model
        // 3. Update the behavioral weights
        // 4. Clear relevant caches
        
        Cache::tags(['ai_matches'])->flush();
    }
}
