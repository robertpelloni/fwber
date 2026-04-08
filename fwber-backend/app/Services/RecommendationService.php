<?php

namespace App\Services;

use App\Models\BulletinBoard;
use App\Models\BulletinMessage;
use App\Models\TelemetryEvent;
use App\Models\Topic;
use App\Models\User;
use App\Models\UserProfile;
use App\Services\Ai\Llm\LlmManager;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class RecommendationService
{
    private LlmManager $llmManager;

    private LocalPulseRankingService $localPulseRankingService;

    private array $recommendationConfig;

    public function __construct(LlmManager $llmManager, LocalPulseRankingService $localPulseRankingService)
    {
        $this->llmManager = $llmManager;
        $this->localPulseRankingService = $localPulseRankingService;
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
    public function getRecommendations(int $userId, array $context = [], ?array $types = null): array
    {
        $requestedTypes = $this->normalizeRequestedTypes($types);
        $cacheKey = "recommendations_{$userId}_".md5(serialize([
            'context' => $context,
            'types' => $requestedTypes,
        ]));

        // Check cache first
        if ($cached = Cache::get($cacheKey)) {
            return $cached;
        }

        $user = User::with(['profile', 'followedTopics'])->find($userId);
        if (! $user) {
            return [];
        }

        $sources = [];

        if (in_array('content', $requestedTypes, true)) {
            $userProfile = $this->getUserProfile($user);
            $userBehavior = $this->getUserBehavior($user);
            $sources['content'] = $this->resolveRecommendationSource('content', function () use ($userProfile, $userBehavior) {
                return $this->getContentBasedRecommendations($userProfile, $userBehavior);
            });
        }

        if (in_array('collaborative', $requestedTypes, true)) {
            $userBehavior = $userBehavior ?? $this->getUserBehavior($user);
            $sources['collaborative'] = $this->resolveRecommendationSource('collaborative', function () use ($user, $userBehavior) {
                return $this->getCollaborativeRecommendations($user, $userBehavior);
            });
        }

        if (in_array('ai', $requestedTypes, true)) {
            $userProfile = $userProfile ?? $this->getUserProfile($user);
            $userBehavior = $userBehavior ?? $this->getUserBehavior($user);
            $contextualData = $this->getContextualData($context);
            $sources['ai'] = $this->resolveRecommendationSource('ai', function () use ($userProfile, $userBehavior, $contextualData) {
                return $this->getAIRecommendations($userProfile, $userBehavior, $contextualData);
            });
        }

        if (in_array('location', $requestedTypes, true)) {
            $sources['location'] = $this->resolveRecommendationSource('location', function () use ($user, $context) {
                return $this->getLocationBasedRecommendations($user, $context);
            });
        }

        // Combine and rank recommendations
        $recommendations = $this->combineRecommendations($sources);
        $recommendations = $this->enrichRecommendationsWithSceneSignals($user, $recommendations);
        $recommendations = $this->applyTrustAwareRanking($user, $recommendations);

        // Apply diversity and freshness filters
        $recommendations = $this->applyDiversityFilter($recommendations);
        $recommendations = $this->applyFreshnessFilter($recommendations);
        $recommendations = $this->finalizeRecommendations($recommendations);

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
            'interests' => $profile?->interests ?? [],
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
            'preferences' => $profile?->preferences ?? [],
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
        if (empty($similarUsers)) {
            return [];
        }

        $similarUserIds = array_column($similarUsers, 'id');
        
        // Bulk fetch content created by similar users
        $allContent = BulletinMessage::whereIn('user_id', $similarUserIds)
            ->with('bulletinBoard')
            ->get()
            ->groupBy('user_id');

        // Pre-fetch seen content IDs for the current user to avoid N+1 queries
        $contentIds = $allContent->flatten()->pluck('id')->unique()->toArray();
        $seenContentIds = [];
        
        if (!empty($contentIds)) {
            $seenEvents = TelemetryEvent::where('user_id', $user->id)
                ->where('event', 'view_content')
                ->whereIn('payload->content_id', $contentIds)
                ->get();
                
            foreach ($seenEvents as $event) {
                if (isset($event->payload['content_id'])) {
                    $seenContentIds[] = $event->payload['content_id'];
                }
            }
        }

        // Get content from similar users that current user hasn't seen
        foreach ($similarUsers as $similarUser) {
            $userId = $similarUser['id'];
            $messages = $allContent->get($userId) ?? collect();
            
            $userContent = $messages->map(function (BulletinMessage $message) {
                return [
                    'id' => $message->id,
                    'title' => $message->bulletinBoard?->name ?? 'Community update',
                    'description' => $message->content,
                    'content' => $message->content,
                    'created_at' => $message->created_at?->toISOString(),
                    'creator_id' => $message->user_id,
                    'bulletin_board_id' => $message->bulletin_board_id,
                ];
            })->all();

            foreach ($userContent as $content) {
                if (! in_array($content['id'], $seenContentIds)) {
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
                ['role' => 'user', 'content' => $prompt],
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
                ['role' => 'user', 'content' => $prompt],
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

        $latitude = $context['latitude'] ?? $profile?->latitude;
        $longitude = $context['longitude'] ?? $profile?->longitude;
        $radius = isset($context['radius']) ? (int) $context['radius'] : 5000;

        if (! is_numeric($latitude) || ! is_numeric($longitude)) {
            return $recommendations;
        }

        $latitude = (float) $latitude;
        $longitude = (float) $longitude;
        $radius = max(100, min($radius, 50000));

        // Find nearby bulletin boards
        $nearbyBoards = BulletinBoard::query()
            ->active()
            ->nearLocation($latitude, $longitude, $radius)
            ->limit(5)
            ->get();

        foreach ($nearbyBoards as $board) {
            $recommendations[] = [
                'type' => 'location',
                'content' => [
                    'id' => $board->id,
                    'name' => $board->name,
                    'description' => $board->description,
                    'distance' => $this->calculateDistance($latitude, $longitude, (float) $board->center_lat, (float) $board->center_lng),
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
        usort($uniqueRecommendations, function ($a, $b) {
            return $b['score'] <=> $a['score'];
        });

        return array_values($uniqueRecommendations);
    }

    private function enrichRecommendationsWithSceneSignals(User $user, array $recommendations): array
    {
        $sceneProfile = $this->buildSceneProfile($user);
        if ($sceneProfile['terms'] === [] && $sceneProfile['topics'] === []) {
            return $recommendations;
        }

        return array_map(function (array $recommendation) use ($sceneProfile) {
            return $this->attachSceneSignals($recommendation, $sceneProfile);
        }, $recommendations);
    }

    private function applyTrustAwareRanking(User $user, array $recommendations): array
    {
        if ($recommendations === []) {
            return $recommendations;
        }

        $authorIds = array_values(array_unique(array_filter(array_map(
            fn (array $recommendation) => $this->extractRecommendationAuthorId($recommendation),
            $recommendations
        ))));

        $trustMap = $this->localPulseRankingService->buildTrustMap($user, $authorIds);

        $rankedRecommendations = array_map(function (array $recommendation) use ($trustMap, $user) {
            $authorId = $this->extractRecommendationAuthorId($recommendation);
            $createdAt = $this->extractRecommendationCreatedAt($recommendation);
            $sceneSignals = isset($recommendation['scene_signals']) && is_array($recommendation['scene_signals'])
                ? $recommendation['scene_signals']
                : null;

            $recommendation['_ranking_score'] = round(
                ((float) ($recommendation['score'] ?? 0) * 40)
                + $this->localPulseRankingService->calculateCompositeScore($user, $authorId, $createdAt, $sceneSignals, $trustMap),
                2
            );

            return $recommendation;
        }, $recommendations);

        usort($rankedRecommendations, function (array $left, array $right) {
            $rankingComparison = (($right['_ranking_score'] ?? 0) <=> ($left['_ranking_score'] ?? 0));
            if ($rankingComparison !== 0) {
                return $rankingComparison;
            }

            return (($right['score'] ?? 0) <=> ($left['score'] ?? 0));
        });

        return $rankedRecommendations;
    }

    private function normalizeRequestedTypes(?array $types): array
    {
        $allowedTypes = ['content', 'collaborative', 'ai', 'location'];
        $requestedTypes = $types === null || $types === []
            ? $allowedTypes
            : array_values(array_intersect($allowedTypes, $types));

        return $requestedTypes === [] ? $allowedTypes : $requestedTypes;
    }

    private function resolveRecommendationSource(string $source, callable $resolver): array
    {
        try {
            $recommendations = $resolver();

            return is_array($recommendations) ? $recommendations : [];
        } catch (\Throwable $exception) {
            Log::error('Recommendation source failed', [
                'source' => $source,
                'error' => $exception->getMessage(),
            ]);

            return [];
        }
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
            $typeCount = count(array_filter($diverseRecommendations, fn ($r) => $r['type'] === $type));

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
        return array_filter($recommendations, function ($rec) {
            $createdAt = $rec['content']['created_at'] ?? null;
            if (! $createdAt) {
                return true;
            }

            $age = now()->diffInHours($createdAt);

            return $age <= 168; // 1 week
        });
    }

    /**
     * @return array{topics: array<int, array{id:int,slug:string,label:string,emoji:string|null,terms:array<int,string>}>, terms: array<int, string>}
     */
    private function buildSceneProfile(User $user): array
    {
        $profile = $user->profile;
        $topics = $user->followedTopics->map(function (Topic $topic) {
            $terms = Topic::normalizeTerms([
                $topic->slug,
                $topic->label,
                ...($topic->aliases ?? []),
            ]);

            return [
                'id' => $topic->id,
                'slug' => $topic->slug,
                'label' => $topic->label,
                'emoji' => $topic->emoji,
                'terms' => $terms,
            ];
        })->values()->all();

        $terms = Topic::normalizeTerms(array_merge(
            $profile?->interests ?? [],
            ...array_map(fn (array $topic) => $topic['terms'], $topics)
        ));

        return [
            'topics' => $topics,
            'terms' => $terms,
        ];
    }

    /**
     * @param  array<string, mixed>  $recommendation
     * @param  array{topics: array<int, array{id:int,slug:string,label:string,emoji:string|null,terms:array<int,string>}>, terms: array<int, string>}  $sceneProfile
     * @return array<string, mixed>
     */
    private function attachSceneSignals(array $recommendation, array $sceneProfile): array
    {
        $content = $recommendation['content'] ?? [];
        $contentTerms = $this->extractSceneTerms(array_filter([
            is_array($content) ? ($content['title'] ?? null) : null,
            is_array($content) ? ($content['name'] ?? null) : null,
            is_array($content) ? ($content['description'] ?? null) : null,
            $recommendation['reason'] ?? null,
        ]));

        if ($contentTerms === []) {
            return $recommendation;
        }

        $matchedTopics = array_values(array_filter($sceneProfile['topics'], function (array $topic) use ($contentTerms) {
            return $this->topicMatchesContentTerms($topic['terms'], $contentTerms);
        }));

        $matchedTags = array_slice(array_values(array_intersect($sceneProfile['terms'], $contentTerms)), 0, 4);
        $boost = min(0.2, (count($matchedTopics) * 0.08) + (count($matchedTags) * 0.03));

        $recommendation['score'] = min(1.0, (float) ($recommendation['score'] ?? 0) + $boost);
        $recommendation['scene_signals'] = [
            'headline' => $this->buildSceneHeadline($matchedTopics, $matchedTags),
            'matched_topics' => array_map(function (array $topic) {
                return [
                    'id' => $topic['id'],
                    'slug' => $topic['slug'],
                    'label' => $topic['label'],
                    'emoji' => $topic['emoji'],
                ];
            }, array_slice($matchedTopics, 0, 3)),
            'matched_tags' => $matchedTags,
            'score_boost' => round($boost, 2),
        ];

        return $recommendation;
    }

    /**
     * @param  array<int, string>  $values
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
    private function buildSceneHeadline(array $matchedTopics, array $matchedTags): ?string
    {
        $parts = [];

        if ($matchedTopics !== []) {
            $parts[] = 'Scene match on '.implode(', ', array_map(fn (array $topic) => $topic['label'], array_slice($matchedTopics, 0, 2)));
        }

        if ($matchedTags !== []) {
            $parts[] = 'tags like '.implode(', ', array_slice($matchedTags, 0, 2));
        }

        return $parts === [] ? null : implode(' • ', $parts);
    }

    private function extractRecommendationAuthorId(array $recommendation): ?int
    {
        $content = isset($recommendation['content']) && is_array($recommendation['content'])
            ? $recommendation['content']
            : [];

        foreach (['creator_id', 'author_id', 'user_id', 'created_by_user_id'] as $key) {
            $value = $content[$key] ?? null;
            if (is_numeric($value)) {
                return (int) $value;
            }
        }

        return null;
    }

    private function extractRecommendationCreatedAt(array $recommendation): ?\Carbon\CarbonInterface
    {
        $content = isset($recommendation['content']) && is_array($recommendation['content'])
            ? $recommendation['content']
            : [];

        $createdAt = $content['created_at'] ?? $recommendation['created_at'] ?? null;
        if (! $createdAt) {
            return null;
        }

        try {
            return \Carbon\Carbon::parse($createdAt);
        } catch (\Throwable) {
            return null;
        }
    }

    private function finalizeRecommendations(array $recommendations): array
    {
        $trimmedRecommendations = array_slice(array_values($recommendations), 0, $this->recommendationConfig['max_recommendations']);

        return array_map(function (array $recommendation) {
            unset($recommendation['_ranking_score']);

            return $recommendation;
        }, $trimmedRecommendations);
    }

    /**
     * Build OpenAI prompt for recommendations
     */
    private function buildOpenAIPrompt(array $userProfile, array $userBehavior, array $contextualData): string
    {
        return "Based on this user profile and behavior, recommend relevant bulletin board content:\n\n".
               'User Profile: '.json_encode($userProfile)."\n".
               'User Behavior: '.json_encode($userBehavior)."\n".
               'Context: '.json_encode($contextualData)."\n\n".
               "Provide 5 personalized recommendations with explanations. Focus on location-based social content that matches their interests and activity patterns.\n".
               "Return the response in strict JSON format as an array of objects with keys: 'type' (string), 'content' (object with 'title', 'description'), 'score' (float 0-1), 'reason' (string).";
    }

    /**
     * Build Gemini prompt for recommendations
     */
    private function buildGeminiPrompt(array $userProfile, array $userBehavior, array $contextualData): string
    {
        return "Analyze this user's profile and behavior to recommend relevant bulletin board content:\n\n".
               'Profile: '.json_encode($userProfile)."\n".
               'Behavior: '.json_encode($userBehavior)."\n".
               'Context: '.json_encode($contextualData)."\n\n".
               "Suggest 5 personalized recommendations with reasoning. Consider their location, interests, and recent activity patterns.\n".
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

            if (! is_array($recommendations)) {
                Log::warning("Failed to parse AI recommendations JSON from {$provider}", ['content' => $content]);

                return [];
            }

            $parsed = [];
            foreach ($recommendations as $rec) {
                if (isset($rec['content'], $rec['reason'])) {
                    $parsed[] = [
                        'type' => 'ai',
                        'content' => $rec['content'],
                        'score' => isset($rec['score']) ? (float) $rec['score'] : 0.8,
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
        $eventCount = $user->attendingEvents()->where('starts_at', '>=', now()->subDays(30))->count();
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
        $candidates = UserProfile::with('user')
            ->where('user_id', '!=', $userId)
            ->whereNotNull('interests')
            ->limit(50) // Limit candidates for performance
            ->get();

        $similarUsers = [];
        $validCandidates = [];

        foreach ($candidates as $candidate) {
            $candidateInterests = $candidate->interests ?? [];
            $similarity = $this->calculateJaccardSimilarity($interests, $candidateInterests);

            if ($similarity > 0.1 && $candidate->user) {
                $validCandidates[] = [
                    'user' => $candidate->user,
                    'similarity' => $similarity,
                ];
            }
        }

        if (!empty($validCandidates)) {
            $candidateIds = array_map(fn($c) => $c['user']->id, $validCandidates);
            
            $likedEvents = TelemetryEvent::whereIn('user_id', $candidateIds)
                ->where('event', 'like_content')
                ->orderBy('created_at', 'desc')
                ->get()
                ->groupBy('user_id');

            foreach ($validCandidates as $c) {
                $user = $c['user'];
                $content = [];
                $userEvents = $likedEvents->get($user->id) ? $likedEvents->get($user->id)->take(10) : collect();
                
                foreach ($userEvents as $event) {
                    if (isset($event->payload['content_id'], $event->payload['content_type'])) {
                        $content[] = [
                            'id' => $event->payload['content_id'],
                            'type' => $event->payload['content_type'],
                            'title' => $event->payload['title'] ?? 'Untitled',
                            'description' => $event->payload['description'] ?? '',
                            'created_at' => $event->created_at?->toISOString(),
                            'creator_id' => isset($event->payload['creator_id']) && is_numeric($event->payload['creator_id'])
                                ? (int) $event->payload['creator_id']
                                : null,
                        ];
                    }
                }

                if (!empty($content)) {
                    $similarUsers[] = [
                        'id' => $user->id,
                        'similarity' => $c['similarity'],
                        'liked_content' => $content,
                    ];
                }
            }
        }

        // Sort by similarity
        usort($similarUsers, fn ($a, $b) => $b['similarity'] <=> $a['similarity']);

        return array_slice($similarUsers, 0, 5);
    }

    /**
     * Calculate Jaccard Similarity between two arrays
     */
    private function calculateJaccardSimilarity(array $setA, array $setB): float
    {
        if (empty($setA) && empty($setB)) {
            return 0.0;
        }

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
                    'created_at' => $event->created_at?->toISOString(),
                    'creator_id' => isset($event->payload['creator_id']) && is_numeric($event->payload['creator_id'])
                        ? (int) $event->payload['creator_id']
                        : null,
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

        // Let's find users who viewed the same boards
        $viewedBoards = $userBehavior['viewed_boards'] ?? [];
        if (empty($viewedBoards)) {
            return [];
        }

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
        if (!empty($similarUserIds)) {
            $userModels = User::whereIn('id', $similarUserIds)->get();
            
            // Pre-fetch all liked events for these users to avoid N+1 inside getUserLikedContent
            $likedEvents = TelemetryEvent::whereIn('user_id', $similarUserIds)
                ->where('event', 'like_content')
                ->orderBy('created_at', 'desc')
                ->get()
                ->groupBy('user_id');

            foreach ($userModels as $u) {
                $content = [];
                $userEvents = $likedEvents->get($u->id) ? $likedEvents->get($u->id)->take(10) : collect();
                
                foreach ($userEvents as $event) {
                    if (isset($event->payload['content_id'], $event->payload['content_type'])) {
                        $content[] = [
                            'id' => $event->payload['content_id'],
                            'type' => $event->payload['content_type'],
                            'title' => $event->payload['title'] ?? 'Untitled',
                            'description' => $event->payload['description'] ?? '',
                            'created_at' => $event->created_at?->toISOString(),
                            'creator_id' => isset($event->payload['creator_id']) && is_numeric($event->payload['creator_id'])
                                ? (int) $event->payload['creator_id']
                                : null,
                        ];
                    }
                }

                $users[] = [
                    'id' => $u->id,
                    'similarity' => 0.5, // Placeholder score
                    'liked_content' => $content,
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
            ->map(function (BulletinMessage $message) {
                return [
                    'id' => $message->id,
                    'title' => $message->bulletinBoard?->name ?? 'Community update',
                    'description' => $message->content,
                    'content' => $message->content,
                    'created_at' => $message->created_at?->toISOString(),
                    'creator_id' => $message->user_id,
                    'bulletin_board_id' => $message->bulletin_board_id,
                ];
            })
            ->all();
    }

    /**
     * Check if user has seen content
     */
    private function hasUserSeenContent(int $userId, int $contentId): bool
    {
        // Check TelemetryEvents for view_content
        // Caching this check would be good for performance
        $cacheKey = "user_seen_{$userId}_{$contentId}";

        return Cache::remember($cacheKey, 3600, function () use ($userId, $contentId) {
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
        $text = $title.' '.$description;

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
        if (! $profile || ! $profile->latitude || ! $profile->longitude) {
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

        $a = sin($dLat / 2) * sin($dLat / 2) + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLon / 2) * sin($dLon / 2);
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c;
    }

    /**
     * Analyze activity patterns
     */
    private function analyzeActivityPatterns(User $user): array
    {
        $timestamps = BulletinMessage::where('user_id', $user->id)
            ->where('created_at', '>=', now()->subDays(90))
            ->pluck('created_at');

        $hours = $timestamps->map(function ($timestamp) {
            try {
                return \Carbon\Carbon::parse($timestamp)->hour;
            } catch (\Throwable) {
                return null;
            }
        })->filter(fn ($hour) => is_int($hour))->values();

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
            if ($hour >= 6 && $hour < 12) {
                $patterns['morning']++;
            } elseif ($hour >= 12 && $hour < 18) {
                $patterns['afternoon']++;
            } elseif ($hour >= 18 && $hour < 24) {
                $patterns['evening']++;
            } else {
                $patterns['night']++;
            }
        }

        $total = $hours->count();

        return array_map(fn ($count) => round($count / $total, 2), $patterns);
    }

    /**
     * Analyze content preferences
     */
    private function analyzeContentPreferences($messages): array
    {
        $text = $messages->pluck('content')->implode(' ');
        $words = str_word_count(strtolower($text), 1);
        $stopWords = ['the', 'and', 'is', 'in', 'at', 'to', 'for', 'with', 'a', 'of', 'it', 'on', 'that', 'this', 'i', 'you', 'my', 'are'];

        $filtered = array_filter($words, fn ($w) => strlen($w) > 3 && ! in_array($w, $stopWords));
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
        if (in_array($month, [12, 1, 2])) {
            return 'winter';
        }
        if (in_array($month, [3, 4, 5])) {
            return 'spring';
        }
        if (in_array($month, [6, 7, 8])) {
            return 'summer';
        }

        return 'fall';
    }

    /**
     * Get trending topics
     */
    private function getTrendingTopics(): array
    {
        // Get messages from the last 24 hours
        $messages = BulletinMessage::where('created_at', '>=', now()->subHours(24))
            ->limit(100) // Limit for performance
            ->pluck('content');

        if ($messages->isEmpty()) {
            return [];
        }

        $text = $messages->implode(' ');
        // Remove special characters and convert to lowercase
        $text = preg_replace('/[^a-zA-Z0-9\s]/', '', strtolower($text));

        $words = str_word_count($text, 1);
        $stopWords = [
            'the', 'and', 'is', 'in', 'at', 'to', 'for', 'with', 'a', 'of', 'it', 'on', 'that', 'this', 'i', 'you', 'my', 'are',
            'was', 'were', 'be', 'been', 'have', 'has', 'had', 'do', 'does', 'did', 'but', 'or', 'as', 'if', 'when', 'than',
            'about', 'from', 'by', 'an', 'so', 'what', 'who', 'which', 'where', 'why', 'how', 'all', 'any', 'both', 'each',
            'few', 'more', 'most', 'other', 'some', 'such', 'no', 'nor', 'not', 'only', 'own', 'same', 'so', 'than', 'too',
            'very', 'can', 'will', 'just', 'should', 'now',
        ];

        $filtered = array_filter($words, fn ($w) => strlen($w) > 3 && ! in_array($w, $stopWords));
        $counts = array_count_values($filtered);
        arsort($counts);

        return array_slice(array_keys($counts), 0, 5);
    }
}
