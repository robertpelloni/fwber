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

    public function findAdvancedMatches(User $user, array $filters = [], int $limit = 20): array
    {
        // Include filters in cache key to ensure unique results for different filter sets
        $filterHash = md5(serialize($filters));
        $cacheKey = "ai_matches_{$user->id}_{$filterHash}";
        
        return Cache::remember($cacheKey, 300, function () use ($user, $filters, $limit) {
            $userProfile = $user->profile;
            if (!$userProfile) {
                return [];
            }

            // Get behavioral preferences
            $behavioralPrefs = $this->analyzeUserBehavior($user);
            
            // Get candidates with basic filters
            $candidates = $this->getCandidates($user, $userProfile, $filters);
            
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
                ->all();
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

    private function getCandidates(User $user, UserProfile $userProfile, array $filters = [])
    {
        $query = User::query()
            ->whereKeyNot($user->id)
            ->whereHas('profile')
            ->with(['profile']);

        // Basic distance filter
        if ($userProfile->location_latitude && $userProfile->location_longitude) {
            $maxDistance = $filters['max_distance'] ?? 50; // Default 50 miles
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

        // Age filter
        if (isset($filters['age_min']) || isset($filters['age_max'])) {
            $ageMin = (int) ($filters['age_min'] ?? 18);
            $ageMax = (int) ($filters['age_max'] ?? 100);
            
            $query->whereHas('profile', function ($q) use ($ageMin, $ageMax) {
                // SQLite-compatible age calculation
                $q->whereRaw("(julianday('now') - julianday(date_of_birth)) / 365.25 BETWEEN ? AND ?", [
                    $ageMin,
                    $ageMax
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

        // Base compatibility score (20%)
        $baseScore = $this->calculateBaseCompatibility($userProfile, $candidateProfile);
        $score += $baseScore * 0.20;

        // Detailed Preference Matching (30%) - New detailed scoring
        $preferenceScore = $this->calculateDetailedPreferenceScore($userProfile, $candidateProfile);
        $score += $preferenceScore * 0.30;

        // Behavioral matching score (15%)
        $behavioralScore = $this->calculateBehavioralScore($candidateProfile, $behavioralPrefs);
        $score += $behavioralScore * 0.15;

        // Communication style similarity (10%)
        $communicationScore = $this->calculateCommunicationScore($userProfile, $candidateProfile);
        $score += $communicationScore * 0.10;

        // Mutual interest score (10%)
        $mutualScore = $this->calculateMutualInterestScore($user, $candidate);
        $score += $mutualScore * 0.10;

        // Recency score (15%)
        $recencyScore = $this->calculateRecencyScore($candidate);
        $score += $recencyScore * 0.15;

        return min(100, max(0, $score));
    }

    private function calculateRecencyScore(User $candidate): float
    {
        if (!$candidate->last_seen_at) {
            return 0;
        }

        $minutesAgo = $candidate->last_seen_at->diffInMinutes(now());

        if ($minutesAgo < 60) { // < 1 hour
            return 100;
        } elseif ($minutesAgo < 1440) { // < 24 hours
            return 80;
        } elseif ($minutesAgo < 10080) { // < 7 days
            return 50;
        } elseif ($minutesAgo < 43200) { // < 30 days
            return 20;
        }

        return 0;
    }

    private function calculateDetailedPreferenceScore(UserProfile $userProfile, UserProfile $candidateProfile): float
    {
        $scores = [
            'physical' => $this->calculatePhysicalScore($userProfile, $candidateProfile),
            'sexual' => $this->calculateSexualScore($userProfile, $candidateProfile),
            'lifestyle' => $this->calculateLifestyleScore($userProfile, $candidateProfile),
            'personality' => $this->calculatePersonalityScore($userProfile, $candidateProfile),
        ];

        // Weights for detailed preferences
        $weights = [
            'physical' => 0.25,
            'sexual' => 0.35, // Higher weight for sexual compatibility in this context
            'lifestyle' => 0.20,
            'personality' => 0.20,
        ];

        $totalScore = 0;
        foreach ($scores as $category => $score) {
            $totalScore += $score * $weights[$category];
        }

        return $totalScore;
    }

    private function calculatePhysicalScore(UserProfile $userProfile, UserProfile $candidateProfile): float
    {
        $score = 0;
        $maxScore = 0;
        $userPrefs = $userProfile->preferences ?? [];
        $candidatePrefs = $candidateProfile->preferences ?? [];

        // Body Type
        $bodyTypes = ['Tiny', 'Slim', 'Average', 'Muscular', 'Curvy', 'Thick', 'BBW'];
        foreach ($bodyTypes as $type) {
            $preference = $userPrefs['want_body_' . strtolower($type)] ?? 0;
            if ($preference) {
                $maxScore += 20;
                if (($candidatePrefs['body_type'] ?? '') === strtolower($type)) {
                    $score += 20 * ($preference / 10); // preference is 1-10
                }
            }
        }

        // Ethnicity
        $ethnicities = ['White', 'Asian', 'Latino', 'Indian', 'Black', 'Other'];
        foreach ($ethnicities as $ethnicity) {
            $preference = $userPrefs['want_ethnicity_' . strtolower($ethnicity)] ?? 0;
            if ($preference) {
                $maxScore += 15;
                if (($candidatePrefs['ethnicity'] ?? '') === strtolower($ethnicity)) {
                    $score += 15 * ($preference / 10);
                }
            }
        }

        // Height & Weight (Simplified)
        // ... implementation ...

        return $maxScore > 0 ? ($score / $maxScore) * 100 : 50;
    }

    private function calculateSexualScore(UserProfile $userProfile, UserProfile $candidateProfile): float
    {
        $score = 0;
        $total = 0;
        $userPrefs = $userProfile->preferences ?? [];
        $candidatePrefs = $candidateProfile->preferences ?? [];

        $sexualActs = [
            'safe_sex', 'bareback', 'oral_give', 'oral_receive',
            'anal_top', 'anal_bottom', 'roleplay', 'dom', 'sub', 
            'kink_spanking', 'kink_bondage'
        ];

        foreach ($sexualActs as $act) {
            $iWant = $userPrefs['want_' . $act] ?? 0;
            $theyWant = $candidatePrefs['want_' . $act] ?? 0;

            if ($iWant || $theyWant) {
                $total++;
                if ($iWant && $theyWant) {
                    $score += 15; // Perfect match
                } elseif ($iWant || $theyWant) {
                    $score += 5; // Partial match
                }
            }
        }

        // Kink Compatibility Bonus
        $kinkMatches = 0;
        $kinks = ['dom', 'sub', 'spanking', 'bondage', 'roleplay'];
        foreach ($kinks as $kink) {
            if (($userPrefs['want_' . $kink] ?? 0) && ($candidatePrefs['want_' . $kink] ?? 0)) {
                $kinkMatches++;
            }
        }
        $score += ($kinkMatches * 5);

        return min(100, $total > 0 ? ($score / max($total * 10, 1)) * 100 : 50);
    }

    private function calculateLifestyleScore(UserProfile $userProfile, UserProfile $candidateProfile): float
    {
        $score = 50; // Base
        $penalties = 0;
        $userPrefs = $userProfile->preferences ?? [];
        $candidatePrefs = $candidateProfile->preferences ?? [];

        // Smoking
        if (($userPrefs['smoke'] ?? 0) && ($candidatePrefs['no_smoke'] ?? 0)) $penalties += 20;
        
        // Drinking
        if (($userPrefs['heavy_drink'] ?? 0) && ($candidatePrefs['no_heavy_drink'] ?? 0)) $penalties += 15;

        // Drugs
        if (($userPrefs['drugs'] ?? 0) && ($candidatePrefs['no_drugs'] ?? 0)) $penalties += 25;

        return max(0, $score - $penalties);
    }

    private function calculatePersonalityScore(UserProfile $userProfile, UserProfile $candidateProfile): float
    {
        $score = 50;
        $userPrefs = $userProfile->preferences ?? [];
        $candidatePrefs = $candidateProfile->preferences ?? [];

        // Bedroom Personality (Complementary matching)
        $bedroomMap = ['passive' => 1, 'shy' => 2, 'confident' => 3, 'aggressive' => 4];
        $myStyle = $bedroomMap[$userPrefs['bedroom_personality'] ?? 'confident'] ?? 3;
        $theirStyle = $bedroomMap[$candidatePrefs['bedroom_personality'] ?? 'confident'] ?? 3;

        if (($myStyle <= 2 && $theirStyle >= 3) || ($myStyle >= 3 && $theirStyle <= 2)) {
            $score += 20; // Opposites attract
        } elseif (abs($myStyle - $theirStyle) <= 1) {
            $score += 10; // Similar
        }

        return min(100, $score);
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
