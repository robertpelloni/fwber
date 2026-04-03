<?php

namespace App\Services;

use App\Models\DateFeedback;
use App\Models\Group;
use App\Models\Journal;
use App\Models\MatchAction;
use App\Models\Topic;
use App\Models\User;
use App\Models\UserProfile;
use App\Support\TaggedCache;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AIMatchingService
{
    private $vectorService;

    public function __construct(VectorService $vectorService)
    {
        $this->vectorService = $vectorService;
    }

    /**
     * Get dynamically tuned behavioral weights.
     */
    private function getBehavioralWeights(): array
    {
        return Cache::remember('ai_behavioral_weights', 86400, function () {
            // Default weights if no dynamic data
            return [
                'profile_view' => 0.1,
                'like' => 0.3,
                'super_like' => 0.5,
                'message_sent' => 0.4,
                'match' => 0.6,
            ];
        });
    }

    public function findAdvancedMatches(User $user, array $filters = [], int $limit = 20): array
    {
        // Include filters in cache key
        $filterHash = md5(serialize($filters));
        $cacheKey = "ai_matches_v2_{$user->id}_{$filterHash}";

        return Cache::remember($cacheKey, 300, function () use ($user, $filters, $limit) {
            $userProfile = $user->profile;
            if (! $userProfile) {
                return [];
            }

            try {
                // 1. Generate embedding for current user query (or use their profile embedding)
                // For now, we use the user's own profile as the query vector
                // We need to re-generate it or fetch it.
                // Better to assume we have it in Redis?
                // Let's generate it on the fly for the query to be sure
                $queryText = $this->vectorService->formatProfileForEmbedding($userProfile);
                $queryVector = $this->vectorService->generateEmbedding($queryText);

                if (empty($queryVector)) {
                    return $this->fallbackToHeuristicMatching($user, $filters, $limit);
                }

                // 2. Search Vector DB for candidates (Get 100 to filtering)
                $vectorResults = $this->vectorService->search($queryVector, 100);
                $candidateIds = array_column($vectorResults, 'user_id');

                if ($candidateIds === []) {
                    return $this->fallbackToHeuristicMatching($user, $filters, $limit);
                }

                // 3. Hydrate Candidates
                $candidates = User::whereIn('id', $candidateIds)
                    ->whereKeyNot($user->id) // Exclude self
                    ->with(['profile', 'followedTopics:id,slug,label,emoji,aliases'])
                    ->get();

                // 4. Apply Hard Filters (Distance, Age, Gender) & Heuristic Scoring
                // Reuse existing scoring logic for Re-Ranking
                $behavioralPrefs = $this->analyzeUserBehavior($user);

                $scoredCandidates = $candidates->map(function ($candidate) use ($user, $userProfile, $behavioralPrefs) {
                    // Filter out if missing profile
                    if (! $candidate->profile) {
                        return null;
                    }

                    // Application Level Filtering (Distance, Age, etc) happens here effectively
                    // because we check compatibility which includes these factors.
                    // However, we should explicitly check hard filters if strict.
                    // For now, let's rely on the score.

                    $score = $this->calculateAdvancedScore($user, $userProfile, $candidate, $behavioralPrefs);
                    $candidate->ai_score = $score;

                    return $candidate;
                })->filter()->values();

                // Sort by combined score
                return $scoredCandidates
                    ->sortByDesc('ai_score')
                    ->take($limit)
                    ->values()
                    ->all();
            } catch (\Throwable $exception) {
                Log::warning('AI matching fell back to heuristic matching', [
                    'user_id' => $user->id,
                    'error' => $exception->getMessage(),
                ]);

                return $this->fallbackToHeuristicMatching($user, $filters, $limit);
            }
        });
    }

    public function fallbackToHeuristicMatching(User $user, array $filters = [], int $limit = 20): array
    {
        $userProfile = $user->profile;
        if (! $userProfile) {
            return [];
        }

        $candidates = $this->getCandidates($user, $userProfile, $filters);

        $behavioralPrefs = $this->analyzeUserBehavior($user);

        $scoredCandidates = $candidates->map(function ($candidate) use ($user, $userProfile, $behavioralPrefs) {
            if (! $candidate->profile) {
                return null;
            }

            $score = $this->calculateAdvancedScore($user, $userProfile, $candidate, $behavioralPrefs);
            $candidate->ai_score = $score;

            return $candidate;
        })->filter()->values();

        return $scoredCandidates
            ->sortByDesc('ai_score')
            ->take($limit)
            ->values()
            ->all();
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

        $weights = $this->getBehavioralWeights();

        foreach ($interactions as $interaction) {
            $targetUser = User::with('profile')->find($interaction->target_user_id);
            if (! $targetUser || ! $targetUser->profile) {
                continue;
            }

            $weight = $weights[$interaction->action] ?? 0.1;
            $count = $interaction->count;

            // Analyze age preferences
            if ($targetUser->profile->birthdate) {
                $age = $targetUser->profile->birthdate->diffInYears(now());
                $behavioralPrefs['preferred_ages'][$age] =
                    ($behavioralPrefs['preferred_ages'][$age] ?? 0) + ($weight * $count);
            }

            // Analyze location preferences
            if ($targetUser->profile->location_name) {
                $location = $targetUser->profile->location_name;
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
            ->with(['profile', 'followedTopics:id,slug,label,emoji,aliases']);

        $normalizedInterestFilters = $this->normalizeInterestValues($filters['interests'] ?? []);

        // Determine effective location for the current user
        $myLat = $userProfile->is_travel_mode ? $userProfile->travel_latitude : $userProfile->latitude;
        $myLon = $userProfile->is_travel_mode ? $userProfile->travel_longitude : $userProfile->longitude;

        // Basic distance filter
        if ($myLat && $myLon) {
            $maxDistance = $filters['max_distance'] ?? 50; // Default 50 miles

            // Calculate bounding box
            $latDist = (1.1 * $maxDistance) / 49.1;
            $lonDist = (1.1 * $maxDistance) / 69.1;

            $minLat = $myLat - $latDist;
            $maxLat = $myLat + $latDist;
            $minLon = $myLon - $lonDist;
            $maxLon = $myLon + $lonDist;

            $query->whereHas('profile', function ($q) use ($minLat, $maxLat, $minLon, $maxLon) {
                $q->where(function ($sub) use ($minLat, $maxLat, $minLon, $maxLon) {
                    // Match users at their real location (if not in travel mode)
                    $sub->where(function ($w) use ($minLat, $maxLat, $minLon, $maxLon) {
                        $w->where('is_travel_mode', false)
                            ->whereBetween('latitude', [$minLat, $maxLat])
                            ->whereBetween('longitude', [$minLon, $maxLon]);
                    })
                    // Or match users who are virtually traveling to my area
                        ->orWhere(function ($w) use ($minLat, $maxLat, $minLon, $maxLon) {
                            $w->where('is_travel_mode', true)
                                ->whereBetween('travel_latitude', [$minLat, $maxLat])
                                ->whereBetween('travel_longitude', [$minLon, $maxLon]);
                        });
                });
            });
        }

        // Age filter
        if (isset($filters['age_min']) || isset($filters['age_max'])) {
            $ageMin = (int) ($filters['age_min'] ?? 18);
            $ageMax = (int) ($filters['age_max'] ?? 100);

            $query->whereHas('profile', function ($q) use ($ageMin, $ageMax) {
                // SQLite-compatible age calculation
                $q->whereRaw("(julianday('now') - julianday(birthdate)) / 365.25 BETWEEN ? AND ?", [
                    $ageMin,
                    $ageMax,
                ]);
            });
        }

        // Advanced Filters
        $query->whereHas('profile', function ($q) use ($filters) {
            // Smoking
            if (! empty($filters['smoking'])) {
                $q->where('smoking_status', $filters['smoking']);
            }

            // Drinking
            if (! empty($filters['drinking'])) {
                $q->where('drinking_status', $filters['drinking']);
            }

            // Body Type
            if (! empty($filters['body_type'])) {
                $q->where('body_type', $filters['body_type']);
            }

            // Height Min
            if (! empty($filters['height_min'])) {
                $q->where('height_cm', '>=', (int) $filters['height_min']);
            }

            // Has Bio
            if (! empty($filters['has_bio']) && $filters['has_bio']) {
                $q->whereNotNull('bio')->where('bio', '!=', '');
            }

            // -- Premium Filters --

            // Cannabis
            if (! empty($filters['cannabis'])) {
                $q->where('cannabis_status', $filters['cannabis']);
            }

            // Diet
            if (! empty($filters['diet'])) {
                $q->where('dietary_preferences', $filters['diet']);
            }

            // Politics
            if (! empty($filters['politics'])) {
                $q->where('political_views', $filters['politics']);
            }

            // Religion
            if (! empty($filters['religion'])) {
                $q->where('religion', $filters['religion']);
            }

            // Zodiac
            if (! empty($filters['zodiac'])) {
                $q->where('zodiac_sign', $filters['zodiac']);
            }

            // Has Pets (Frontend sends 'yes'/'no', DB is boolean)
            if (! empty($filters['has_pets'])) {
                $q->where('has_pets', $filters['has_pets'] === 'yes');
            }

            // Has Children
            if (! empty($filters['has_children'])) {
                $q->where('has_children', $filters['has_children'] === 'yes');
            }

            // Wants Children
            if (! empty($filters['wants_children'])) {
                $q->where('wants_children', $filters['wants_children'] === 'yes');
            }
        });

        if ($normalizedInterestFilters !== []) {
            $query->whereHas('profile', function ($q) use ($normalizedInterestFilters) {
                $q->where(function ($interestQuery) use ($normalizedInterestFilters) {
                    foreach ($normalizedInterestFilters as $interest) {
                        $interestQuery->orWhereJsonContains('interests', $interest);
                    }
                });
            });
        }

        // Verified Only
        if (! empty($filters['verified_only']) && $filters['verified_only']) {
            $query->whereNotNull('email_verified_at');
        }

        // Incognito Mode Logic
        // Get IDs of users who have liked the current user
        $likerIds = MatchAction::where('target_user_id', $user->id)
            ->whereIn('action', ['like', 'super_like'])
            ->pluck('user_id')
            ->toArray();

        $query->whereHas('profile', function ($q) use ($likerIds) {
            $q->where(function ($sub) use ($likerIds) {
                // Show if NOT incognito
                $sub->where('is_incognito', false)
                    // OR if they are incognito but have liked me
                    ->orWhere(function ($incognito) use ($likerIds) {
                        $incognito->where('is_incognito', true)
                            ->whereIn('user_id', $likerIds);
                    });
            });
        });

        // Exclude users already interacted with
        $excludedIds = MatchAction::where('user_id', $user->id)
            ->pluck('target_user_id')
            ->toArray();

        if (! empty($excludedIds)) {
            $query->whereNotIn('id', $excludedIds);
        }

        return $query->limit(100)->get(); // Get more candidates for AI scoring
    }

    private function calculateAdvancedScore(User $user, UserProfile $userProfile, User $candidate, array $behavioralPrefs): float
    {
        $score = 0;
        $candidateProfile = $candidate->profile;

        if (! $candidateProfile) {
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

        // Scene affinity score (10%) favors shared followed topics and structured scene cues.
        $sceneScore = $this->calculateSceneAffinityScore($user, $candidate);
        $score += $sceneScore * 0.10;

        // Recency score (10%)
        $recencyScore = $this->calculateRecencyScore($candidate);
        $score += $recencyScore * 0.10;

        // Post-Date Feedback Modifier (up to +/- 15 points based on historical ratings of similar profiles)
        $feedbackModifier = $this->calculateDateFeedbackModifier($user, $candidateProfile);
        $score += $feedbackModifier;

        return min(100, max(0, $score));
    }

    public function getCompatibilityBreakdown(User $user, User $candidate): array
    {
        $userProfile = $user->profile;
        $candidateProfile = $candidate->profile;

        if (! $userProfile || ! $candidateProfile) {
            return [];
        }

        // Get behavioral preferences
        $behavioralPrefs = $this->analyzeUserBehavior($user);

        // Calculate individual scores
        $baseScore = $this->calculateBaseCompatibility($userProfile, $candidateProfile);
        $preferenceScore = $this->calculateDetailedPreferenceScore($userProfile, $candidateProfile);
        $behavioralScore = $this->calculateBehavioralScore($candidateProfile, $behavioralPrefs);
        $communicationScore = $this->calculateCommunicationScore($userProfile, $candidateProfile);
        $mutualScore = $this->calculateMutualInterestScore($user, $candidate);
        $sceneScore = $this->calculateSceneAffinityScore($user, $candidate);
        $recencyScore = $this->calculateRecencyScore($candidate);

        // Calculate total weighted score
        $totalScore = ($baseScore * 0.20) +
                      ($preferenceScore * 0.30) +
                      ($behavioralScore * 0.15) +
                      ($communicationScore * 0.10) +
                      ($mutualScore * 0.10) +
                      ($sceneScore * 0.10) +
                      ($recencyScore * 0.10);

        return [
            'total_score' => round(min(100, max(0, $totalScore))),
            'breakdown' => [
                'base' => round($baseScore),
                'preferences' => round($preferenceScore),
                'behavioral' => round($behavioralScore),
                'communication' => round($communicationScore),
                'mutual' => round($mutualScore),
                'scene' => round($sceneScore),
                'recency' => round($recencyScore),
            ],
            'details' => [
                'physical' => round($this->calculatePhysicalScore($userProfile, $candidateProfile)),
                'sexual' => round($this->calculateSexualScore($userProfile, $candidateProfile)),
                'lifestyle' => round($this->calculateLifestyleScore($userProfile, $candidateProfile)),
                'personality' => round($this->calculatePersonalityScore($userProfile, $candidateProfile)),
            ],
        ];
    }

    private function calculateRecencyScore(User $candidate): float
    {
        if (! $candidate->last_seen_at) {
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
            $preference = $userPrefs['want_body_'.strtolower($type)] ?? 0;
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
            $preference = $userPrefs['want_ethnicity_'.strtolower($ethnicity)] ?? 0;
            if ($preference) {
                $maxScore += 15;
                if (($candidatePrefs['ethnicity'] ?? '') === strtolower($ethnicity)) {
                    $score += 15 * ($preference / 10);
                }
            }
        }

        // Hair Color
        if (isset($userPrefs['preferred_hair_colors']) && is_array($userPrefs['preferred_hair_colors'])) {
            $maxScore += 10;
            if (in_array($candidateProfile->hair_color, $userPrefs['preferred_hair_colors'])) {
                $score += 10;
            }
        }

        // Eye Color
        if (isset($userPrefs['preferred_eye_colors']) && is_array($userPrefs['preferred_eye_colors'])) {
            $maxScore += 10;
            if (in_array($candidateProfile->eye_color, $userPrefs['preferred_eye_colors'])) {
                $score += 10;
            }
        }

        // Height
        if (isset($userPrefs['min_height']) && isset($userPrefs['max_height']) && $candidateProfile->height_cm) {
            $maxScore += 15;
            if ($candidateProfile->height_cm >= $userPrefs['min_height'] &&
                $candidateProfile->height_cm <= $userPrefs['max_height']) {
                $score += 15;
            }
        }

        // Breast Size
        if (isset($userPrefs['preferred_breast_sizes']) && is_array($userPrefs['preferred_breast_sizes'])) {
            $maxScore += 10;
            if (in_array($candidateProfile->breast_size, $userPrefs['preferred_breast_sizes'])) {
                $score += 10;
            }
        }

        // Tattoos & Piercings
        if (isset($userPrefs['preferred_tattoos']) && is_array($userPrefs['preferred_tattoos'])) {
            $maxScore += 5;
            if (in_array($candidateProfile->tattoos, $userPrefs['preferred_tattoos'])) {
                $score += 5;
            }
        }

        return $maxScore > 0 ? ($score / $maxScore) * 100 : 50;
    }

    private function calculateSexualScore(UserProfile $userProfile, UserProfile $candidateProfile): float
    {
        $score = 0;
        $total = 0;
        $userPrefs = $userProfile->preferences ?? [];
        $candidatePrefs = $candidateProfile->preferences ?? [];

        // Penis Size Preferences
        if ($candidateProfile->penis_length_cm) {
            $minLen = $userPrefs['min_penis_length'] ?? 0;
            $maxLen = $userPrefs['max_penis_length'] ?? 100;

            if ($minLen > 0 || $maxLen < 100) {
                $total++;
                if ($candidateProfile->penis_length_cm >= $minLen && $candidateProfile->penis_length_cm <= $maxLen) {
                    $score += 20;
                }
            }
        }

        if ($candidateProfile->penis_girth_cm) {
            $minGirth = $userPrefs['min_penis_girth'] ?? 0;
            $maxGirth = $userPrefs['max_penis_girth'] ?? 100;

            if ($minGirth > 0 || $maxGirth < 100) {
                $total++;
                if ($candidateProfile->penis_girth_cm >= $minGirth && $candidateProfile->penis_girth_cm <= $maxGirth) {
                    $score += 20;
                }
            }
        }

        $sexualActs = [
            'safe_sex', 'bareback', 'oral_give', 'oral_receive',
            'anal_top', 'anal_bottom', 'roleplay', 'dom', 'sub',
            'kink_spanking', 'kink_bondage',
        ];

        foreach ($sexualActs as $act) {
            $iWant = $userPrefs['want_'.$act] ?? 0;
            $theyWant = $candidatePrefs['want_'.$act] ?? 0;

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
            if (($userPrefs['want_'.$kink] ?? 0) && ($candidatePrefs['want_'.$kink] ?? 0)) {
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
        if (($userPrefs['smoke'] ?? 0) && ($candidatePrefs['no_smoke'] ?? 0)) {
            $penalties += 20;
        }

        // Drinking
        if (($userPrefs['heavy_drink'] ?? 0) && ($candidatePrefs['no_heavy_drink'] ?? 0)) {
            $penalties += 15;
        }

        // Drugs
        if (($userPrefs['drugs'] ?? 0) && ($candidatePrefs['no_drugs'] ?? 0)) {
            $penalties += 25;
        }

        // Children Compatibility
        if (isset($userPrefs['wants_children']) && isset($candidateProfile->wants_children)) {
            if ($userPrefs['wants_children'] !== $candidateProfile->wants_children) {
                $penalties += 15;
            }
        }

        // Dealbreaker: Has Kids
        if (($userPrefs['no_kids'] ?? false) && $candidateProfile->has_children) {
            $penalties += 50;
        }

        // Pets Compatibility
        if (($userPrefs['must_love_pets'] ?? false) && ! $candidateProfile->has_pets) {
            $penalties += 10;
        }

        // Love Language Compatibility
        if ($userProfile->love_language && $candidateProfile->love_language) {
            if ($userProfile->love_language === $candidateProfile->love_language) {
                $score += 10;
            }
        }

        // Personality Compatibility (MBTI/Simple)
        if ($userProfile->personality_type && $candidateProfile->personality_type) {
            // Simple logic: Introverts match well with Extroverts often, but let's keep it simple for now
            // Or maybe similar types match? Let's assume similarity for now unless specified otherwise
            if ($userProfile->personality_type === $candidateProfile->personality_type) {
                $score += 5;
            }
        }

        // Political Views Compatibility
        if ($userProfile->political_views && $candidateProfile->political_views) {
            if ($userProfile->political_views === $candidateProfile->political_views) {
                $score += 10;
            } elseif (
                ($userProfile->political_views === 'liberal' && $candidateProfile->political_views === 'conservative') ||
                ($userProfile->political_views === 'conservative' && $candidateProfile->political_views === 'liberal')
            ) {
                $penalties += 10; // Potential friction
            }
        }

        // Religion Compatibility
        if ($userProfile->religion && $candidateProfile->religion) {
            if ($userProfile->religion === $candidateProfile->religion) {
                $score += 10;
            }
        }

        // Sleep Schedule Compatibility
        if ($userProfile->sleep_schedule && $candidateProfile->sleep_schedule) {
            if ($userProfile->sleep_schedule === $candidateProfile->sleep_schedule) {
                $score += 5;
            } elseif (
                ($userProfile->sleep_schedule === 'early_bird' && $candidateProfile->sleep_schedule === 'night_owl') ||
                ($userProfile->sleep_schedule === 'night_owl' && $candidateProfile->sleep_schedule === 'early_bird')
            ) {
                $penalties += 5; // Minor friction
            }
        }

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
        if ($userProfile->birthdate && $candidateProfile->birthdate) {
            $userAge = $userProfile->birthdate->diffInYears(now());
            $candidateAge = $candidateProfile->birthdate->diffInYears(now());
            $ageDiff = abs($userAge - $candidateAge);
            $score += max(0, 20 - $ageDiff);
        }

        // Distance compatibility
        $myLat = $userProfile->is_travel_mode ? $userProfile->travel_latitude : $userProfile->latitude;
        $theirLat = $candidateProfile->is_travel_mode ? $candidateProfile->travel_latitude : $candidateProfile->latitude;

        if ($myLat && $theirLat) {
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
        if ($candidateProfile->birthdate) {
            $age = $candidateProfile->birthdate->diffInYears(now());
            $ageScore = $behavioralPrefs['preferred_ages'][$age] ?? 0;
            $score += min(25, $ageScore);
        }

        // Location preference matching
        if ($candidateProfile->location_name) {
            $locationScore = $behavioralPrefs['preferred_locations'][$candidateProfile->location_name] ?? 0;
            $score += min(25, $locationScore);
        }

        // Gender preference matching
        if ($candidateProfile->gender) {
            $genderScore = $behavioralPrefs['preferred_genders'][$candidateProfile->gender] ?? 0;
            $score += min(25, $genderScore);
        }

        return $score;
    }

    private function calculateDateFeedbackModifier(User $user, UserProfile $candidateProfile): float
    {
        $modifier = 0;

        // Fetch user's historical date feedback
        $feedbacks = DateFeedback::where('reporting_user_id', $user->id)
            ->with('subject.profile')
            ->get();

        if ($feedbacks->isEmpty() || ! $candidateProfile->personality_type) {
            return 0; // No historical data or nothing to compare against
        }

        foreach ($feedbacks as $feedback) {
            $pastProfile = $feedback->subject?->profile;
            if (! $pastProfile) {
                continue;
            }

            // If safety concerns were flagged for a highly similar demographic/personality, apply a strong penalty.
            // (Assuming personality type is the primary heuristic for 'similarity' here)
            $isSimilar = $pastProfile->personality_type === $candidateProfile->personality_type;

            if ($isSimilar) {
                if ($feedback->safety_concerns) {
                    $modifier -= 15; // Strong penalty for safety patterns
                } elseif ($feedback->rating <= 2) {
                    $modifier -= 5;  // Negative feedback pattern
                } elseif ($feedback->rating >= 4) {
                    $modifier += 5;  // Positive feedback pattern
                }
            }
        }

        // Cap the modifier influence
        return min(15, max(-20, $modifier));
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

        $sharedInterestScore = $this->calculateSharedInterestScore($user->profile, $candidate->profile);

        return min(100, ($mutualInterest ? 50 : 0) + $sharedInterestScore);
    }

    /**
     * @return array<int, string>
     */
    public function getSharedInterests(UserProfile $userProfile, UserProfile $candidateProfile, int $limit = 3): array
    {
        $sharedInterests = array_values(array_intersect(
            $this->normalizeInterestValues($userProfile->interests ?? []),
            $this->normalizeInterestValues($candidateProfile->interests ?? [])
        ));

        return array_slice($sharedInterests, 0, $limit);
    }

    /**
     * @return array<string, mixed>
     */
    public function getSceneOverlap(User $user, User $candidate, int $limit = 3): array
    {
        $sharedTopics = $this->getSharedFollowedTopics($user, $candidate, $limit);
        $sharedSceneTags = array_slice(array_values(array_intersect(
            $this->getViewerSceneTags($user),
            $this->getCandidateSceneTags($candidate)
        )), 0, $limit + 2);

        $headlineParts = [];
        if ($sharedTopics !== []) {
            $headlineParts[] = collect($sharedTopics)->pluck('label')->implode(', ');
        }
        if ($sharedSceneTags !== []) {
            $headlineParts[] = implode(', ', array_slice($sharedSceneTags, 0, 2));
        }

        return [
            'headline' => $headlineParts === [] ? null : 'Shared scenes: '.implode(' • ', $headlineParts),
            'score' => (int) round($this->calculateSceneAffinityScore($user, $candidate)),
            'shared_topics' => $sharedTopics,
            'shared_topic_count' => count($sharedTopics),
            'shared_scene_tags' => $sharedSceneTags,
            'shared_scene_tag_count' => count($sharedSceneTags),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function getSceneSummary(User $user, int $topicLimit = 4, int $tagLimit = 6): array
    {
        $followedTopics = $this->getMinimalTopicsForUser($user, $topicLimit);
        $sceneTags = array_slice(array_values(array_unique(array_merge(
            $this->normalizeInterestValues($user->profile?->interests ?? []),
            $this->getGroupSceneTags($user),
            $this->getJournalSceneTags($user)
        ))), 0, $tagLimit);

        $headlineSource = array_merge(
            collect($followedTopics)->pluck('label')->all(),
            $sceneTags
        );

        return [
            'headline' => $headlineSource === [] ? null : 'Currently orbiting '.implode(', ', array_slice($headlineSource, 0, 3)),
            'followed_topics' => $followedTopics,
            'scene_tags' => $sceneTags,
            'stats' => [
                'followed_topic_count' => $this->getFollowedTopicCollection($user)->count(),
                'visible_journal_count' => $this->getVisibleJournalsForScene($user)->count(),
                'public_group_count' => $this->getPublicGroupsForScene($user)->count(),
            ],
        ];
    }

    /**
     * @param  array<int, mixed>  $values
     * @return array<string, mixed>|null
     */
    public function getSceneSignalsForValues(User $user, array $values, int $topicLimit = 3, int $tagLimit = 4): ?array
    {
        $contentTerms = $this->extractSceneTerms($values);
        if ($contentTerms === []) {
            return null;
        }

        $matchedTopics = array_values(array_filter(
            $this->getFollowedTopicCollection($user)
                ->map(function (Topic $topic) {
                    return [
                        'id' => $topic->id,
                        'slug' => $topic->slug,
                        'label' => $topic->label,
                        'emoji' => $topic->emoji,
                        'terms' => Topic::normalizeTerms([
                            $topic->slug,
                            $topic->label,
                            ...($topic->aliases ?? []),
                        ]),
                    ];
                })
                ->values()
                ->all(),
            fn (array $topic) => $this->topicMatchesContentTerms($topic['terms'], $contentTerms)
        ));

        $matchedTags = array_slice(array_values(array_intersect(
            $this->getViewerSceneTags($user),
            $contentTerms
        )), 0, $tagLimit);

        if ($matchedTopics === [] && $matchedTags === []) {
            return null;
        }

        $boost = min(0.2, (count($matchedTopics) * 0.08) + (count($matchedTags) * 0.03));

        return [
            'headline' => $this->buildSceneSignalHeadline($matchedTopics, $matchedTags),
            'matched_topics' => array_map(function (array $topic) {
                return [
                    'id' => $topic['id'],
                    'slug' => $topic['slug'],
                    'label' => $topic['label'],
                    'emoji' => $topic['emoji'],
                ];
            }, array_slice($matchedTopics, 0, $topicLimit)),
            'matched_tags' => $matchedTags,
            'score_boost' => round($boost, 2),
        ];
    }

    private function calculateSharedInterestScore(?UserProfile $userProfile, ?UserProfile $candidateProfile): float
    {
        if (! $userProfile || ! $candidateProfile) {
            return 0;
        }

        $myInterests = $this->normalizeInterestValues($userProfile->interests ?? []);
        $theirInterests = $this->normalizeInterestValues($candidateProfile->interests ?? []);

        if ($myInterests === [] || $theirInterests === []) {
            return 0;
        }

        $sharedInterests = array_intersect($myInterests, $theirInterests);
        if ($sharedInterests === []) {
            return 0;
        }

        $overlapRatio = count($sharedInterests) / max(count($myInterests), count($theirInterests));

        return $overlapRatio * 50;
    }

    private function calculateSceneAffinityScore(User $user, User $candidate): float
    {
        if (! $user->profile || ! $candidate->profile) {
            return 0;
        }

        $sharedTopics = $this->getSharedFollowedTopics($user, $candidate, 10);
        $sharedSceneTags = array_intersect(
            $this->getViewerSceneTags($user),
            $this->getCandidateSceneTags($candidate)
        );

        $topicScore = min(60, count($sharedTopics) * 25);
        $tagScore = min(40, count($sharedSceneTags) * 8);

        return min(100, $topicScore + $tagScore);
    }

    /**
     * @return array<int, array{id:int,slug:string,label:string,emoji:string|null}>
     */
    private function getSharedFollowedTopics(User $user, User $candidate, int $limit = 3): array
    {
        $viewerTopics = $this->getFollowedTopicCollection($user)->keyBy('slug');

        return $this->getFollowedTopicCollection($candidate)
            ->filter(fn (Topic $topic) => $viewerTopics->has($topic->slug))
            ->take($limit)
            ->map(fn (Topic $topic) => [
                'id' => $topic->id,
                'slug' => $topic->slug,
                'label' => $topic->label,
                'emoji' => $topic->emoji,
            ])
            ->values()
            ->all();
    }

    /**
     * @return array<int, array{id:int,slug:string,label:string,emoji:string|null}>
     */
    private function getMinimalTopicsForUser(User $user, int $limit): array
    {
        return $this->getFollowedTopicCollection($user)
            ->take($limit)
            ->map(fn (Topic $topic) => [
                'id' => $topic->id,
                'slug' => $topic->slug,
                'label' => $topic->label,
                'emoji' => $topic->emoji,
            ])
            ->values()
            ->all();
    }

    /**
     * @return \Illuminate\Support\Collection<int, Topic>
     */
    private function getFollowedTopicCollection(User $user): Collection
    {
        if ($user->relationLoaded('followedTopics')) {
            return $user->followedTopics;
        }

        return $user->followedTopics()
            ->select('topics.id', 'topics.slug', 'topics.label', 'topics.emoji', 'topics.aliases')
            ->orderBy('topic_user_follows.followed_at', 'desc')
            ->get();
    }

    /**
     * @return array<int, string>
     */
    private function getViewerSceneTags(User $user): array
    {
        $topicTerms = $this->getFollowedTopicCollection($user)
            ->flatMap(fn (Topic $topic) => array_filter([$topic->slug, $topic->label, ...($topic->aliases ?? [])]))
            ->all();

        return array_values(array_unique(array_merge(
            $this->normalizeInterestValues($user->profile?->interests ?? []),
            $this->normalizeInterestValues($topicTerms)
        )));
    }

    /**
     * @return array<int, string>
     */
    private function getCandidateSceneTags(User $candidate): array
    {
        return array_values(array_unique(array_merge(
            $this->getViewerSceneTags($candidate),
            $this->getGroupSceneTags($candidate),
            $this->getJournalSceneTags($candidate)
        )));
    }

    /**
     * @return array<int, string>
     */
    private function getGroupSceneTags(User $user): array
    {
        return $this->getPublicGroupsForScene($user)
            ->flatMap(fn (Group $group) => array_filter([
                $group->category,
                ...((array) ($group->tags ?? [])),
            ]))
            ->pipe(fn (Collection $values) => $this->normalizeInterestValues($values->all()));
    }

    /**
     * @return array<int, string>
     */
    private function getJournalSceneTags(User $user): array
    {
        return $this->getVisibleJournalsForScene($user)
            ->flatMap(fn (Journal $journal) => (array) ($journal->tags ?? []))
            ->pipe(fn (Collection $values) => $this->normalizeInterestValues($values->all()));
    }

    /**
     * @return \Illuminate\Support\Collection<int, Group>
     */
    private function getPublicGroupsForScene(User $user): Collection
    {
        if ($user->relationLoaded('groups')) {
            return $user->groups
                ->where('privacy', 'public')
                ->values();
        }

        return $user->groups()
            ->select('groups.id', 'groups.category', 'groups.tags', 'groups.privacy')
            ->where('groups.privacy', 'public')
            ->get();
    }

    /**
     * @return \Illuminate\Support\Collection<int, Journal>
     */
    private function getVisibleJournalsForScene(User $user): Collection
    {
        if ($user->relationLoaded('journals')) {
            return $user->journals;
        }

        return $user->journals()
            ->select('id', 'user_id', 'visibility', 'tags')
            ->where('visibility', 'public')
            ->latest()
            ->limit(5)
            ->get();
    }

    /**
     * @param  array<int, mixed>  $values
     * @return array<int, string>
     */
    private function extractSceneTerms(array $values): array
    {
        $tokens = [];

        foreach ($values as $value) {
            if (! is_string($value)) {
                continue;
            }

            $tokens[] = $value;

            $parts = preg_split('/[^a-z0-9]+/iu', mb_strtolower($value)) ?: [];
            foreach ($parts as $part) {
                if ($part !== '') {
                    $tokens[] = $part;
                }
            }
        }

        return Topic::normalizeTerms($tokens);
    }

    /**
     * @param  array<int, string>  $topicTerms
     * @param  array<int, string>  $contentTerms
     */
    private function topicMatchesContentTerms(array $topicTerms, array $contentTerms): bool
    {
        foreach ($topicTerms as $topicTerm) {
            foreach ($contentTerms as $contentTerm) {
                if ($topicTerm === $contentTerm
                    || str_contains($topicTerm, $contentTerm)
                    || str_contains($contentTerm, $topicTerm)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * @param  array<int, array{id:int,slug:string,label:string,emoji:string|null,terms:array<int,string>}>  $matchedTopics
     * @param  array<int, string>  $matchedTags
     */
    private function buildSceneSignalHeadline(array $matchedTopics, array $matchedTags): ?string
    {
        $parts = [];

        if ($matchedTopics !== []) {
            $parts[] = 'Scene match on '.implode(', ', array_map(
                fn (array $topic) => $topic['label'],
                array_slice($matchedTopics, 0, 2)
            ));
        }

        if ($matchedTags !== []) {
            $parts[] = 'tags like '.implode(', ', array_slice($matchedTags, 0, 2));
        }

        return $parts === [] ? null : implode(' • ', $parts);
    }

    /**
     * @param  mixed  $values
     * @return array<int, string>
     */
    private function normalizeInterestValues($values): array
    {
        if (! is_array($values)) {
            return [];
        }

        $normalized = array_map(function ($value) {
            if (! is_string($value)) {
                return null;
            }

            $value = preg_replace('/\s+/', ' ', trim($value));
            if ($value === null || $value === '') {
                return null;
            }

            return mb_strtolower($value);
        }, $values);

        return array_values(array_unique(array_filter($normalized)));
    }

    private function calculateDistance(UserProfile $profile1, UserProfile $profile2): float
    {
        $lat1 = $profile1->is_travel_mode ? $profile1->travel_latitude : $profile1->latitude;
        $lon1 = $profile1->is_travel_mode ? $profile1->travel_longitude : $profile1->longitude;

        $lat2 = $profile2->is_travel_mode ? $profile2->travel_latitude : $profile2->latitude;
        $lon2 = $profile2->is_travel_mode ? $profile2->travel_longitude : $profile2->longitude;

        if (! $lat1 || ! $lat2) {
            return 0;
        }

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
        if (! $userProfile->preferences || ! $candidateProfile->preferences) {
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

        if (! $userProfile->preferences || ! $candidateProfile->preferences) {
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
        Log::info('Updating behavioral matching model');

        // Calculate new weights based on actual engagement success over the last 30 days
        $totalActions = MatchAction::where('created_at', '>=', now()->subDays(30))->count();

        if ($totalActions < 100) {
            Log::info('Not enough data to retrain behavioral model (using defaults)');

            return;
        }

        // Count successful conversions (likes -> matches, messages -> dates)
        $actionCounts = MatchAction::where('created_at', '>=', now()->subDays(30))
            ->select('action', DB::raw('COUNT(*) as count'))
            ->groupBy('action')
            ->pluck('count', 'action')
            ->toArray();

        // Calculate relative weights
        $likes = $actionCounts['like'] ?? 1;
        $superLikes = $actionCounts['super_like'] ?? 1;
        $matches = DB::table('user_matches')->where('created_at', '>=', now()->subDays(30))->count() ?: 1;

        // Simple probability heuristic: how indicative is an action of a match?
        $likeWeight = min(0.5, ($matches / $likes) * 1.5);
        $superLikeWeight = min(0.8, ($matches / $superLikes) * 2.0);

        // Normalize and update weights
        $newWeights = [
            'profile_view' => 0.05,
            'like' => max(0.1, $likeWeight),
            'super_like' => max(0.2, $superLikeWeight),
            'message_sent' => 0.4,
            'match' => 0.7,
        ];

        Cache::put('ai_behavioral_weights', $newWeights, 86400); // Cache for 24 hours

        Log::info('Behavioral model updated with new weights', $newWeights);
        TaggedCache::flush(['ai_matches']);
    }

    public function generateDateIdeas(User $user1, User $user2, ?string $location = null): array
    {
        $openaiKey = config('services.openai.api_key');
        if (! $openaiKey) {
            // Fallback mock ideas if OpenAI fails or is not configured
            return [
                'ideas' => [
                    [
                        'title' => 'Coffee & Walk',
                        'description' => 'A casual meetup at a local cafe and a walk in the park.',
                        'reason' => 'Low pressure setting to get to know each other.',
                        'venue' => $location ?? 'Local Coffee Shop',
                        'estimated_cost' => '$10-$20',
                        'duration' => '1.5 hours',
                        'conversation_starter' => 'What is a typical Sunday like for you?',
                    ],
                ],
            ];
        }

        $prompt = "You are an expert dating concierge AI. Your task is to generate 3-5 highly creative, personalized date itineraries for two matched users based on their profiles. Return the response as a JSON object matching this schema: {\"ideas\": [{\"title\": \"string\", \"description\": \"string\", \"reason\": \"string (why this fits both profiles)\", \"venue\": \"string (optional location name based on provided location parameter)\", \"estimated_cost\": \"string (e.g., $$-$$$)\", \"duration\": \"string (e.g., 2 hours)\", \"conversation_starter\": \"string (a unique opening question related to the date/profiles)\"}]} \n\n"
                .'User 1 Profile: '.json_encode($user1->profile?->toArray() ?? [])."\n"
                .'User 2 Profile: '.json_encode($user2->profile?->toArray() ?? [])."\n";

        if ($location) {
            $prompt .= 'Target Location Context: '.$location."\n";
        }

        try {
            $response = \Illuminate\Support\Facades\Http::withToken($openaiKey)
                ->timeout(15) // Give AI time to think
                ->post('https://api.openai.com/v1/chat/completions', [
                    'model' => 'gpt-4o-mini',
                    'messages' => [
                        ['role' => 'system', 'content' => 'You must always return valid JSON that perfectly matches the requested schema.'],
                        ['role' => 'user', 'content' => $prompt],
                    ],
                    'response_format' => ['type' => 'json_object'],
                    'temperature' => 0.7,
                ]);

            if ($response->successful()) {
                $content = $response->json('choices.0.message.content');
                if ($content) {
                    $decoded = json_decode($content, true);
                    if (json_last_error() === JSON_ERROR_NONE && isset($decoded['ideas'])) {
                        return $decoded;
                    }
                }
            }
        } catch (\Exception $e) {
            Log::error('OpenAI Date Planner Failed: '.$e->getMessage());
        }

        return [
            'ideas' => [
                [
                    'title' => 'Casual Coffee Connection',
                    'description' => 'A relaxed meetup at a nearby cafe.',
                    'reason' => 'A great, low-pressure way to start a connection.',
                    'venue' => $location ?? 'Local Cafe',
                    'estimated_cost' => '$10-$20',
                    'duration' => '1-2 hours',
                    'conversation_starter' => 'What was the highlight of your week?',
                ],
            ],
        ];
    }
}
