<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use App\Models\User;
use App\Models\BulletinBoard;
use App\Models\BulletinMessage;

class RecommendationService
{
    private string $openaiApiKey;
    private string $geminiApiKey;
    private array $recommendationConfig;

    public function __construct()
    {
        $this->openaiApiKey = config('services.openai.api_key');
        $this->geminiApiKey = config('services.gemini.api_key');
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
    private function getUserProfile(User $user): array
    {
        return [
            'id' => $user->id,
            'interests' => $user->interests ?? [],
            'age' => $user->age,
            'location' => [
                'latitude' => $user->latitude,
                'longitude' => $user->longitude,
            ],
            'preferences' => $user->preferences ?? [],
            'activity_level' => $this->calculateActivityLevel($user),
            'engagement_score' => $this->calculateEngagementScore($user),
        ];
    }

    /**
     * Get user behavior data
     */
    private function getUserBehavior(User $user): array
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
    private function getContextualData(array $context): array
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
    private function getContentBasedRecommendations(array $userProfile, array $userBehavior): array
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
    private function getCollaborativeRecommendations(User $user, array $userBehavior): array
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
    private function getAIRecommendations(array $userProfile, array $userBehavior, array $contextualData): array
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
    private function getOpenAIRecommendations(array $userProfile, array $userBehavior, array $contextualData): array
    {
        try {
            $prompt = $this->buildOpenAIPrompt($userProfile, $userBehavior, $contextualData);
            
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->openaiApiKey,
                'Content-Type' => 'application/json',
            ])->post('https://api.openai.com/v1/chat/completions', [
                'model' => 'gpt-4',
                'messages' => [
                    ['role' => 'system', 'content' => 'You are a recommendation engine for a location-based social platform. Provide personalized content recommendations based on user profile and behavior.'],
                    ['role' => 'user', 'content' => $prompt]
                ],
                'max_tokens' => 1000,
                'temperature' => 0.7,
            ]);

            if ($response->successful()) {
                $data = $response->json();
                $content = $data['choices'][0]['message']['content'] ?? '';
                return $this->parseAIRecommendations($content, 'openai');
            }
        } catch (\Exception $e) {
            Log::error('OpenAI recommendations failed', ['error' => $e->getMessage()]);
        }

        return [];
    }

    /**
     * Get Gemini recommendations
     */
    private function getGeminiRecommendations(array $userProfile, array $userBehavior, array $contextualData): array
    {
        try {
            $prompt = $this->buildGeminiPrompt($userProfile, $userBehavior, $contextualData);
            
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
            ])->post("https://generativelanguage.googleapis.com/v1beta/models/gemini-pro:generateContent?key={$this->geminiApiKey}", [
                'contents' => [
                    [
                        'parts' => [
                            ['text' => $prompt]
                        ]
                    ]
                ],
                'generationConfig' => [
                    'temperature' => 0.7,
                    'maxOutputTokens' => 1000,
                ]
            ]);

            if ($response->successful()) {
                $data = $response->json();
                $content = $data['candidates'][0]['content']['parts'][0]['text'] ?? '';
                return $this->parseAIRecommendations($content, 'gemini');
            }
        } catch (\Exception $e) {
            Log::error('Gemini recommendations failed', ['error' => $e->getMessage()]);
        }

        return [];
    }

    /**
     * Get location-based recommendations
     */
    private function getLocationBasedRecommendations(User $user, array $context): array
    {
        $recommendations = [];
        
        if (!$user->latitude || !$user->longitude) {
            return $recommendations;
        }

        // Find nearby bulletin boards
        $nearbyBoards = BulletinBoard::whereRaw('ST_Distance_Sphere(location, POINT(?, ?)) <= ?', [
            $user->longitude,
            $user->latitude,
            5000 // 5km radius
        ])->where('is_active', true)
        ->orderByRaw('ST_Distance_Sphere(location, POINT(?, ?))', [$user->longitude, $user->latitude])
        ->limit(5)
        ->get();

        foreach ($nearbyBoards as $board) {
            $recommendations[] = [
                'type' => 'location',
                'content' => [
                    'id' => $board->id,
                    'name' => $board->name,
                    'description' => $board->description,
                    'distance' => $this->calculateDistance($user->latitude, $user->longitude, $board->center_lat, $board->center_lng),
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
            $key = $rec['content']['id'] ?? md5($rec['content']);
            
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
               "Provide 5 personalized recommendations with explanations. Focus on location-based social content that matches their interests and activity patterns.";
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
               "Suggest 5 personalized recommendations with reasoning. Consider their location, interests, and recent activity patterns.";
    }

    /**
     * Parse AI recommendations from response
     */
    private function parseAIRecommendations(string $content, string $provider): array
    {
        // This would parse the AI response and convert to recommendation format
        // For now, return empty array - would need to implement parsing logic
        return [];
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
        // This would calculate based on various engagement metrics
        return 0.5; // Placeholder
    }

    /**
     * Find similar users based on profile
     */
    private function findSimilarUsers(array $userProfile): array
    {
        // This would implement user similarity algorithm
        return [];
    }

    /**
     * Find users with similar behavior
     */
    private function findUsersWithSimilarBehavior(array $userBehavior): array
    {
        // This would implement collaborative filtering
        return [];
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
        // This would check user's view history
        return false;
    }

    /**
     * Calculate content score
     */
    private function calculateContentScore(array $content, array $userProfile): float
    {
        // This would implement content scoring algorithm
        return rand(0, 100) / 100;
    }

    /**
     * Calculate collaborative score
     */
    private function calculateCollaborativeScore(array $similarUser, array $content): float
    {
        // This would implement collaborative filtering score
        return rand(0, 100) / 100;
    }

    /**
     * Calculate location score
     */
    private function calculateLocationScore(BulletinBoard $board, User $user): float
    {
        $distance = $this->calculateDistance($user->latitude, $user->longitude, $board->center_lat, $board->center_lng);
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
        // This would analyze when user is most active
        return [];
    }

    /**
     * Analyze content preferences
     */
    private function analyzeContentPreferences($messages): array
    {
        // This would analyze what type of content user engages with
        return [];
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
