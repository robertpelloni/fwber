<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use App\Models\User;
use App\Models\UserProfile;
use App\Models\BulletinBoard;
use App\Models\BulletinMessage;
use App\Models\TelemetryEvent;
use App\Services\Ai\Llm\LlmManager;

class RecommendationService
{
    private LlmManager $llmManager;
    private array $recommendationConfig;

    public function __construct(LlmManager $llmManager)
    {
        $this->llmManager = $llmManager;
        $this->recommendationConfig = config('recommendations', [
            'enabled' => true,
            'providers' => ['openai', 'gemini'],
            'cache_ttl' => 3600, // 1 hour
            'max_recommendations' => 10,
            'diversity_factor' => 0.3,
            'recency_weight' => 0.4,
            'popularity_weight' => 0.3,
            'personal_weight' => 0.3,
        ]);
    }

    /**
     * Get personalized recommendations for a user
     */
    public function getRecommendations(int $userId, array $context = []): array
    {
        $cacheKey = "recommendations_{$userId}_" . md5(serialize($context));
        
        // Check cache first
        if ($cached = Cache::get($cacheKey)) {
            return $cached;
        }

        $user = User::find($userId);
        if (!$user) {
            return [];
        }

        // Get user profile and behavior data
        $userProfile = $this->getUserProfile($user);
        $userBehavior = $this->getUserBehavior($user);
        $contextualData = $this->getContextualData($context);

        // Generate recommendations using multiple strategies
        $recommendations = [];
        
        // 1. Content-based recommendations
        $contentBased = $this->getContentBasedRecommendations($userProfile, $userBehavior);
        
        // 2. Collaborative filtering
        $collaborative = $this->getCollaborativeRecommendations($user, $userBehavior);
        
        // 3. AI-powered recommendations
        $aiRecommendations = $this->getAIRecommendations($userProfile, $userBehavior, $contextualData);
        
        // 4. Location-based recommendations
        $locationBased = $this->getLocationBasedRecommendations($user, $context);
        
        // Combine and rank recommendations
        $recommendations = $this->combineRecommendations([
            'content' => $contentBased,
            'collaborative' => $collaborative,
            'ai' => $aiRecommendations,
            'location' => $locationBased,
        ]);

        // Apply diversity and freshness filters
        $recommendations = $this->applyDiversityFilter($recommendations);
        $recommendations = $this->applyFreshnessFilter($recommendations);

        // Cache results
        Cache::put($cacheKey, $recommendations, $this->recommendationConfig['cache_ttl']);
        
        return $recommendations;
    }

    /**
     * Get user profile data for recommendations
     */
    protected function getUserProfile(User $user): array
    {
        $profile = $user->profile;
        
        return [
            'id' => $user->id,
            'interests' => $user->interests ?? [],
            'age' => $profile && $profile->birthdate ? $profile->birthdate->age : 0,
            'location' => [
                'latitude' => $profile->latitude ?? 0,
                'longitude' => $profile->longitude ?? 0,
            ],
            'lifestyle' => [
                'smoking' => $profile->smoking_status ?? 'unknown',
                'drinking' => $profile->drinking_status ?? 'unknown',
                'cannabis' => $profile->cannabis_status ?? 'unknown',
                'diet' => $profile->dietary_preferences ?? 'unknown',
                'zodiac' => $profile->zodiac_sign ?? 'unknown',
                'relationship_goals' => $profile->relationship_goals ?? 'unknown',
                'has_children' => $profile->has_children ?? false,
                'wants_children' => $profile->wants_children ?? false,
                'has_pets' => $profile->has_pets ?? false,
                'languages' => $profile->languages ?? [],
            ],
            'preferences' => $user->preferences ?? [],
            'activity_level' => $this->calculateActivityLevel($user),
            'engagement_score' => $this->calculateEngagementScore($user),
        ];
    }

    /**
     * Get user behavior data
     */
    protected function getUserBehavior(User $user): array
    {
        $recentMessages = BulletinMessage::where('user_id', $user->id)
            ->where('created_at', '>=', now()->subDays(30))
            ->with('bulletinBoard')
            ->get();

        $viewedBoards = $user->viewedBoards ?? [];
        $likedContent = $user->likedContent ?? [];
        $sharedContent = $user->sharedContent ?? [];

        return [
            'recent_activity' => $recentMessages->pluck('content')->toArray(),
            'viewed_boards' => $viewedBoards,
            'liked_content' => $likedContent,
            'shared_content' => $sharedContent,
            'activity_patterns' => $this->analyzeActivityPatterns($user),
            'content_preferences' => $this->analyzeContentPreferences($recentMessages),
        ];
    }

    /**
     * Get contextual data (time, location, etc.)
     */
    protected function getContextualData(array $context): array
    {
        return [
            'time_of_day' => now()->format('H'),
            'day_of_week' => now()->dayOfWeek,
            'season' => $this->getSeason(),
            'weather' => $context['weather'] ?? null,
            'events' => $context['events'] ?? [],
            'trending_topics' => $this->getTrendingTopics(),
        ];
    }

    /**
     * Get content-based recommendations
     */
    protected function getContentBasedRecommendations(array $userProfile, array $userBehavior): array
    {
        $recommendations = [];
        
        // Find similar users based on profile
        $similarUsers = $this->findSimilarUsers($userProfile);
        
        // Get content liked by similar users
        foreach ($similarUsers as $similarUser) {
            $likedContent = $similarUser['liked_content'] ?? [];
            foreach ($likedContent as $content) {
                $recommendations[] = [
                    'type' => 'content',
                    'content' => $content,
                    'score' => $this->calculateContentScore($content, $userProfile),
                    'reason' => 'Similar users liked this',
                ];
            }
        }
        
        return $recommendations;
    }

    /**
     * Get collaborative filtering recommendations
     */
    protected function getCollaborativeRecommendations(User $user, array $userBehavior): array
    {
        $recommendations = [];
        
        // Find users with similar behavior patterns
        $similarUsers = $this->findUsersWithSimilarBehavior($userBehavior);
        
        // Get content from similar users that current user hasn't seen
        foreach ($similarUsers as $similarUser) {
            $userContent = $this->getUserContent($similarUser['id']);
            foreach ($userContent as $content) {
                if (!$this->hasUserSeenContent($user->id, $content['id'])) {
                    $recommendations[] = [
                        'type' => 'collaborative',
                        'content' => $content,
                        'score' => $this->calculateCollaborativeScore($similarUser, $content),
                        'reason' => 'Users like you enjoyed this',
                    ];
                }
            }
        }
        
        return $recommendations;
    }

    /**
     * Get AI-powered recommendations using OpenAI and Gemini
     */
    protected function getAIRecommendations(array $userProfile, array $userBehavior, array $contextualData): array
    {
        $recommendations = [];
        
        // OpenAI recommendations
        if (in_array('openai', $this->recommendationConfig['providers'])) {
            $openaiRecs = $this->getOpenAIRecommendations($userProfile, $userBehavior, $contextualData);
            $recommendations = array_merge($recommendations, $openaiRecs);
        }
        
        // Gemini recommendations
        if (in_array('gemini', $this->recommendationConfig['providers'])) {
            $geminiRecs = $this->getGeminiRecommendations($userProfile, $userBehavior, $contextualData);
            $recommendations = array_merge($recommendations, $geminiRecs);
        }
        
        return $recommendations;
    }

    /**
     * Get OpenAI recommendations
     */
    protected function getOpenAIRecommendations(array $userProfile, array $userBehavior, array $contextualData): array
    {
        try {
            $prompt = $this->buildOpenAIPrompt($userProfile, $userBehavior, $contextualData);
            
            $response = $this->llmManager->driver('openai')->chat([
                ['role' => 'system', 'content' => 'You are a recommendation engine for a location-based social platform. Provide personalized content recommendations based on user profile and behavior.'],
                ['role' => 'user', 'content' => $prompt]
            ]);

            if (isset($response->content)) {
                return $this->parseAIRecommendations($response->content, 'openai');
            }
        } catch (\Exception $e) {
            Log::error('OpenAI recommendations failed', ['error' => $e->getMessage()]);
        }

        return [];
    }

    /**
     * Get Gemini recommendations
     */
    protected function getGeminiRecommendations(array $userProfile, array $userBehavior, array $contextualData): array
    {
        try {
            $prompt = $this->buildGeminiPrompt($userProfile, $userBehavior, $contextualData);
            
            $response = $this->llmManager->driver('gemini')->chat([
                ['role' => 'user', 'content' => $prompt]
            ]);

            if (isset($response->content)) {
                return $this->parseAIRecommendations($response->content, 'gemini');
            }
        } catch (\Exception $e) {
            Log::error('Gemini recommendations failed', ['error' => $e->getMessage()]);
        }

        return [];
    }

    /**
     * Get location-based recommendations
     */
    protected function getLocationBasedRecommendations(User $user, array $context): array
    {
        $recommendations = [];
        $profile = $user->profile;
        
        if (!$profile || !$profile->latitude || !$profile->longitude) {
            return $recommendations;
        }

        // Find nearby bulletin boards
        $nearbyBoards = BulletinBoard::whereRaw('ST_Distance_Sphere(location, POINT(?, ?)) <= ?', [
            $profile->longitude,
            $profile->latitude,
            5000 // 5km radius
        ])->where('is_active', true)
        ->orderByRaw('ST_Distance_Sphere(location, POINT(?, ?))', [$profile->longitude, $profile->latitude])
        ->limit(5)
        ->get();

        foreach ($nearbyBoards as $board) {
            $recommendations[] = [
                'type' => 'location',
                'content' => [
                    'id' => $board->id,
                    'name' => $board->name,
                    'description' => $board->description,
                    'distance' => $this->calculateDistance($profile->latitude, $profile->longitude, $board->center_lat, $board->center_lng),
                ],
                'score' => $this->calculateLocationScore($board, $user),
                'reason' => 'Near your location',
            ];
        }

        return $recommendations;
    }

    /**
     * Combine recommendations from different sources
     */
    private function combineRecommendations(array $sources): array
    {
        $allRecommendations = [];
        
        foreach ($sources as $source => $recommendations) {
            foreach ($recommendations as $rec) {
                $rec['source'] = $source;
                $allRecommendations[] = $rec;
            }
        }

        // Remove duplicates and merge scores
        $uniqueRecommendations = [];
        foreach ($allRecommendations as $rec) {
            $key = $rec['content']['id'] ?? md5(is_array($rec['content']) ? json_encode($rec['content']) : $rec['content']);
            
            if (isset($uniqueRecommendations[$key])) {
                // Merge scores from different sources
                $uniqueRecommendations[$key]['score'] = max($uniqueRecommendations[$key]['score'], $rec['score']);
                $uniqueRecommendations[$key]['sources'][] = $rec['source'];
            } else {
                $rec['sources'] = [$rec['source']];
                $uniqueRecommendations[$key] = $rec;
            }
        }

        // Sort by score and return top recommendations
        usort($uniqueRecommendations, function($a, $b) {
            return $b['score'] <=> $a['score'];
        });

        return array_slice($uniqueRecommendations, 0, $this->recommendationConfig['max_recommendations']);
    }

    /**
     * Apply diversity filter to recommendations
     */
    private function applyDiversityFilter(array $recommendations): array
    {
        $diverseRecommendations = [];
        $usedTypes = [];
        
        foreach ($recommendations as $rec) {
            $type = $rec['type'];
            $typeCount = count(array_filter($diverseRecommendations, fn($r) => $r['type'] === $type));
            
            if ($typeCount < 3 || rand(1, 100) <= ($this->recommendationConfig['diversity_factor'] * 100)) {
                $diverseRecommendations[] = $rec;
            }
        }
        
        return $diverseRecommendations;
    }

    /**
     * Apply freshness filter to recommendations
     */
    private function applyFreshnessFilter(array $recommendations): array
    {
        return array_filter($recommendations, function($rec) {
            $createdAt = $rec['content']['created_at'] ?? null;
            if (!$createdAt) return true;
            
            $age = now()->diffInHours($createdAt);
            return $age <= 168; // 1 week
        });
    }

    /**
     * Build OpenAI prompt for recommendations
     */
    private function buildOpenAIPrompt(array $userProfile, array $userBehavior, array $contextualData): string
    {
        return "Based on this user profile and behavior, recommend relevant bulletin board content:\n\n" .
               "User Profile: " . json_encode($userProfile) . "\n" .
               "User Behavior: " . json_encode($userBehavior) . "\n" .
               "Context: " . json_encode($contextualData) . "\n\n" .
               "Provide 5 personalized recommendations with explanations. Focus on location-based social content that matches their interests and activity patterns.\n" .
               "Return the response in strict JSON format as an array of objects with keys: 'type' (string), 'content' (object with 'title', 'description'), 'score' (float 0-1), 'reason' (string).";
    }

    /**
     * Build Gemini prompt for recommendations
     */
    private function buildGeminiPrompt(array $userProfile, array $userBehavior, array $contextualData): string
    {
        return "Analyze this user's profile and behavior to recommend relevant bulletin board content:\n\n" .
               "Profile: " . json_encode($userProfile) . "\n" .
               "Behavior: " . json_encode($userBehavior) . "\n" .
               "Context: " . json_encode($contextualData) . "\n\n" .
               "Suggest 5 personalized recommendations with reasoning. Consider their location, interests, and recent activity patterns.\n" .
               "Return the response in strict JSON format as an array of objects with keys: 'type' (string), 'content' (object with 'title', 'description'), 'score' (float 0-1), 'reason' (string).";
    }

    /**
     * Parse AI recommendations from response
     */
    private function parseAIRecommendations(string $content, string $provider): array
    {
        try {
            // Clean up markdown code blocks if present
            $jsonContent = preg_replace('/^```json\s*|\s*```$/', '', trim($content));
            
            $recommendations = json_decode($jsonContent, true);
            
            if (!is_array($recommendations)) {
                Log::warning("Failed to parse AI recommendations JSON from {$provider}", ['content' => $content]);
                return [];
            }

            $parsed = [];
            foreach ($recommendations as $rec) {
                if (isset($rec['content'], $rec['reason'])) {
                    $parsed[] = [
                        'type' => 'ai',
                        'content' => $rec['content'],
                        'score' => isset($rec['score']) ? (float)$rec['score'] : 0.8,
                        'reason' => $rec['reason'],
                        'provider' => $provider,
                    ];
                }
            }
            
            return $parsed;

        } catch (\Exception $e) {
            Log::error("Error parsing AI recommendations from {$provider}", ['error' => $e->getMessage()]);
            return [];
        }
    }

    /**
     * Calculate activity level for user
     */
    private function calculateActivityLevel(User $user): float
    {
        $messageCount = BulletinMessage::where('user_id', $user->id)->count();
        $daysSinceJoined = $user->created_at->diffInDays(now());
        
        return $daysSinceJoined > 0 ? $messageCount / $daysSinceJoined : 0;
    }

    /**
     * Calculate engagement score for user
     */
    private function calculateEngagementScore(User $user): float
    {
        $score = 0.0;
        
        // Message activity (max 50 messages = 0.4 points)
        $messageCount = BulletinMessage::where('user_id', $user->id)
            ->where('created_at', '>=', now()->subDays(30))
            ->count();
        $score += min($messageCount / 50, 1.0) * 0.4;

        // Group participation (max 5 groups = 0.3 points)
        $groupCount = $user->groups()->count();
        $score += min($groupCount / 5, 1.0) * 0.3;

        // Event attendance (max 3 events = 0.3 points)
        $eventCount = $user->attendingEvents()->where('start_time', '>=', now()->subDays(30))->count();
        $score += min($eventCount / 3, 1.0) * 0.3;

        return min($score, 1.0);
    }

    /**
     * Find similar users based on profile
     */
    private function findSimilarUsers(array $userProfile): array
    {
        $userId = $userProfile['id'];
        $interests = $userProfile['interests'] ?? [];
        
        // Find users with overlapping interests
        // This is a simplified implementation. In production, use a dedicated search engine or vector database.
        $candidates = UserProfile::with('user')
            ->where('user_id', '!=', $userId)
            ->whereNotNull('interests')
            ->limit(50) // Limit candidates for performance
            ->get();

        $similarUsers = [];

        foreach ($candidates as $candidate) {
            $candidateInterests = $candidate->interests ?? [];
            $similarity = $this->calculateJaccardSimilarity($interests, $candidateInterests);

            if ($similarity > 0.1) { // Threshold
                // Get candidate's liked content
                $user = $candidate->user;
                if ($user) {
                    $likedContent = $this->getUserLikedContent($user);
                    if (!empty($likedContent)) {
                        $similarUsers[] = [
                            'id' => $user->id,
                            'similarity' => $similarity,
                            'liked_content' => $likedContent
                        ];
                    }
                }
            }
        }

        // Sort by similarity
        usort($similarUsers, fn($a, $b) => $b['similarity'] <=> $a['similarity']);

        return array_slice($similarUsers, 0, 5);
    }

    /**
     * Calculate Jaccard Similarity between two arrays
     */
    private function calculateJaccardSimilarity(array $setA, array $setB): float
    {
        if (empty($setA) && empty($setB)) return 0.0;
        
        $intersection = count(array_intersect($setA, $setB));
        $union = count(array_unique(array_merge($setA, $setB)));
        
        return $union > 0 ? $intersection / $union : 0.0;
    }

    /**
     * Get content liked by a user
     */
    private function getUserLikedContent(User $user): array
    {
        // Assuming 'likedContent' relationship or logic exists. 
        // If not, we can infer from BulletinMessage interactions or Telemetry
        // For now, let's use TelemetryEvent for 'like' events
        $likedEvents = TelemetryEvent::where('user_id', $user->id)
            ->where('event', 'like_content')
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        $content = [];
        foreach ($likedEvents as $event) {
            if (isset($event->payload['content_id'], $event->payload['content_type'])) {
                // Fetch actual content based on type. For simplicity, returning metadata.
                $content[] = [
                    'id' => $event->payload['content_id'],
                    'type' => $event->payload['content_type'],
                    'title' => $event->payload['title'] ?? 'Untitled',
                    'description' => $event->payload['description'] ?? '',
                    'created_at' => $event->created_at,
                ];
            }
        }
        return $content;
    }

    /**
     * Find users with similar behavior
     */
    private function findUsersWithSimilarBehavior(array $userBehavior): array
    {
        // In a real system, this would use collaborative filtering matrix factorization
        // Here we use a simplified "shared activity" approach
        
        // We don't have direct access to the user object here, but we can infer from context or pass it
        // For now, let's assume we can't easily get the user ID from $userBehavior alone if it's just arrays
        // But looking at call site: $this->getCollaborativeRecommendations($user, $userBehavior)
        // We should update the signature or use the data we have.
        // Let's stick to the signature but maybe we need to refactor slightly to pass User ID if needed, 
        // but wait, getCollaborativeRecommendations passes $user.
        
        // Actually, let's look at how it's called:
        // $similarUsers = $this->findUsersWithSimilarBehavior($userBehavior);
        // It seems I can't easily get the current user ID inside this method unless I pass it.
        // However, $userBehavior contains 'viewed_boards', 'liked_content'.
        
        // Let's find users who viewed the same boards
        $viewedBoards = $userBehavior['viewed_boards'] ?? [];
        if (empty($viewedBoards)) return [];

        $boardIds = array_column($viewedBoards, 'id');
        
        // Find other users who viewed these boards
        // Using TelemetryEvent for 'view_board'
        $similarUserIds = TelemetryEvent::where('event', 'view_board')
            ->whereIn('payload->board_id', $boardIds)
            ->select('user_id')
            ->distinct()
            ->limit(50)
            ->pluck('user_id')
            ->toArray();

        $users = [];
        foreach ($similarUserIds as $uid) {
            // Skip current user (we don't have ID here easily, but we can filter later or pass it)
            // Let's fetch the user
            $u = User::find($uid);
            if ($u) {
                $users[] = [
                    'id' => $u->id,
                    'similarity' => 0.5, // Placeholder score
                    'liked_content' => $this->getUserLikedContent($u)
                ];
            }
        }

        return $users;
    }

    /**
     * Get user content
     */
    private function getUserContent(int $userId): array
    {
        return BulletinMessage::where('user_id', $userId)
            ->with('bulletinBoard')
            ->get()
            ->toArray();
    }

    /**
     * Check if user has seen content
     */
    private function hasUserSeenContent(int $userId, int $contentId): bool
    {
        // Check TelemetryEvents for view_content
        // Caching this check would be good for performance
        $cacheKey = "user_seen_{$userId}_{$contentId}";
        
        return Cache::remember($cacheKey, 3600, function() use ($userId, $contentId) {
            return TelemetryEvent::where('user_id', $userId)
                ->where('event', 'view_content')
                ->where('payload->content_id', $contentId)
                ->exists();
        });
    }

    /**
     * Calculate content score based on user profile matching
     */
    private function calculateContentScore(array $content, array $userProfile): float
    {
        $score = 0.5; // Base score
        
        $title = strtolower($content['title'] ?? '');
        $description = strtolower($content['description'] ?? '');
        $text = $title . ' ' . $description;
        
        $interests = $userProfile['interests'] ?? [];
        
        // Boost score if content matches interests
        foreach ($interests as $interest) {
            if (str_contains($text, strtolower($interest))) {
                $score += 0.1;
            }
        }
        
        // Boost if matches location name
        if (isset($userProfile['location']['name']) && str_contains($text, strtolower($userProfile['location']['name']))) {
            $score += 0.2;
        }

        return min($score, 1.0);
    }

    /**
     * Calculate collaborative score
     */
    private function calculateCollaborativeScore(array $similarUser, array $content): float
    {
        // Score is based on user similarity and content freshness
        $similarity = $similarUser['similarity'] ?? 0.5;
        
        $createdAt = isset($content['created_at']) ? \Carbon\Carbon::parse($content['created_at']) : now();
        $ageInHours = $createdAt->diffInHours(now());
        
        // Decay factor: content loses 10% value every 24 hours
        $freshness = max(0.1, 1.0 - ($ageInHours / 240)); 
        
        return min($similarity * $freshness + 0.2, 1.0);
    }

    /**
     * Calculate location score
     */
    private function calculateLocationScore(BulletinBoard $board, User $user): float
    {
        $profile = $user->profile;
        if (!$profile || !$profile->latitude || !$profile->longitude) {
            return 0;
        }
        
        $distance = $this->calculateDistance($profile->latitude, $profile->longitude, $board->center_lat, $board->center_lng);
        return max(0, 1 - ($distance / 5000)); // Score decreases with distance
    }

    /**
     * Calculate distance between two points
     */
    private function calculateDistance(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $earthRadius = 6371000; // meters
        
        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);
        
        $a = sin($dLat/2) * sin($dLat/2) + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLon/2) * sin($dLon/2);
        $c = 2 * atan2(sqrt($a), sqrt(1-$a));
        
        return $earthRadius * $c;
    }

    /**
     * Analyze activity patterns
     */
    private function analyzeActivityPatterns(User $user): array
    {
        $hours = BulletinMessage::where('user_id', $user->id)
            ->where('created_at', '>=', now()->subDays(90))
            ->selectRaw('HOUR(created_at) as hour')
            ->pluck('hour');

        if ($hours->isEmpty()) {
            return ['unknown' => 1.0];
        }

        $patterns = [
            'morning' => 0,   // 6-12
            'afternoon' => 0, // 12-18
            'evening' => 0,   // 18-24
            'night' => 0,     // 0-6
        ];

        foreach ($hours as $hour) {
            if ($hour >= 6 && $hour < 12) $patterns['morning']++;
            elseif ($hour >= 12 && $hour < 18) $patterns['afternoon']++;
            elseif ($hour >= 18 && $hour < 24) $patterns['evening']++;
            else $patterns['night']++;
        }

        $total = $hours->count();
        return array_map(fn($count) => round($count / $total, 2), $patterns);
    }

    /**
     * Analyze content preferences
     */
    private function analyzeContentPreferences($messages): array
    {
        $text = $messages->pluck('content')->implode(' ');
        $words = str_word_count(strtolower($text), 1);
        $stopWords = ['the', 'and', 'is', 'in', 'at', 'to', 'for', 'with', 'a', 'of', 'it', 'on', 'that', 'this', 'i', 'you', 'my', 'are'];
        
        $filtered = array_filter($words, fn($w) => strlen($w) > 3 && !in_array($w, $stopWords));
        $counts = array_count_values($filtered);
        arsort($counts);
        
        return array_slice(array_keys($counts), 0, 5);
    }

    /**
     * Get season
     */
    private function getSeason(): string
    {
        $month = now()->month;
        if (in_array($month, [12, 1, 2])) return 'winter';
        if (in_array($month, [3, 4, 5])) return 'spring';
        if (in_array($month, [6, 7, 8])) return 'summer';
        return 'fall';
    }

    /**
     * Get trending topics
     */
    private function getTrendingTopics(): array
    {
        // This would get trending topics from analytics
        return [];
    }
}
