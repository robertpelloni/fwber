<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Services\RecommendationService;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

class RecommendationController extends Controller
{
    private RecommendationService $recommendationService;

    public function __construct(RecommendationService $recommendationService)
    {
        $this->recommendationService = $recommendationService;
    }

    /**
     * Get personalized recommendations for the authenticated user
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            if (!$user) {
                return response()->json(['error' => 'Unauthorized'], 401);
            }

            // Validate request parameters
            $validator = Validator::make($request->all(), [
                'limit' => 'integer|min:1|max:50',
                'types' => 'array',
                'types.*' => 'string|in:content,collaborative,ai,location',
                'context' => 'array',
                'context.latitude' => 'numeric|between:-90,90',
                'context.longitude' => 'numeric|between:-180,180',
                'context.radius' => 'integer|min:100|max:50000',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'error' => 'Validation failed',
                    'details' => $validator->errors()
                ], 422);
            }

            $limit = $request->get('limit', 10);
            $types = $request->get('types', ['content', 'collaborative', 'ai', 'location']);
            $context = $request->get('context', []);

            // Add user's current location to context if not provided
            if (!isset($context['latitude']) && $user->latitude) {
                $context['latitude'] = $user->latitude;
            }
            if (!isset($context['longitude']) && $user->longitude) {
                $context['longitude'] = $user->longitude;
            }

            // Get recommendations
            $recommendations = $this->recommendationService->getRecommendations($user->id, $context);

            // Filter by requested types
            $filteredRecommendations = array_filter($recommendations, function($rec) use ($types) {
                return in_array($rec['type'], $types);
            });

            // Apply limit
            $filteredRecommendations = array_slice($filteredRecommendations, 0, $limit);

            // Add metadata
            $response = [
                'recommendations' => $filteredRecommendations,
                'metadata' => [
                    'total' => count($filteredRecommendations),
                    'types' => $types,
                    'context' => $context,
                    'generated_at' => now()->toISOString(),
                    'cache_hit' => false, // Would be true if served from cache
                ]
            ];

            return response()->json($response);

        } catch (\Exception $e) {
            Log::error('Recommendation generation failed', [
                'user_id' => $request->user()?->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'error' => 'Failed to generate recommendations',
                'message' => 'Please try again later'
            ], 500);
        }
    }

    /**
     * Get recommendations for a specific content type
     */
    public function byType(Request $request, string $type): JsonResponse
    {
        $validTypes = ['content', 'collaborative', 'ai', 'location'];
        
        if (!in_array($type, $validTypes)) {
            return response()->json([
                'error' => 'Invalid recommendation type',
                'valid_types' => $validTypes
            ], 422);
        }

        $request->merge(['types' => [$type]]);
        return $this->index($request);
    }

    /**
     * Get trending recommendations
     */
    public function trending(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            if (!$user) {
                return response()->json(['error' => 'Unauthorized'], 401);
            }

            $limit = $request->get('limit', 10);
            $timeframe = $request->get('timeframe', '24h'); // 24h, 7d, 30d

            // Get trending content based on recent activity
            $trendingContent = $this->getTrendingContent($timeframe, $limit);

            $response = [
                'trending' => $trendingContent,
                'metadata' => [
                    'timeframe' => $timeframe,
                    'total' => count($trendingContent),
                    'generated_at' => now()->toISOString(),
                ]
            ];

            return response()->json($response);

        } catch (\Exception $e) {
            Log::error('Trending recommendations failed', [
                'user_id' => $request->user()?->id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'error' => 'Failed to get trending recommendations'
            ], 500);
        }
    }

    /**
     * Get personalized feed for the user
     */
    public function feed(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            if (!$user) {
                return response()->json(['error' => 'Unauthorized'], 401);
            }

            $page = $request->get('page', 1);
            $perPage = $request->get('per_page', 20);
            $offset = ($page - 1) * $perPage;

            // Get personalized feed combining recommendations and recent activity
            $feed = $this->getPersonalizedFeed($user->id, $perPage, $offset);

            $response = [
                'feed' => $feed['items'],
                'pagination' => [
                    'current_page' => $page,
                    'per_page' => $perPage,
                    'total' => $feed['total'],
                    'has_more' => $feed['has_more'],
                ],
                'metadata' => [
                    'generated_at' => now()->toISOString(),
                    'user_id' => $user->id,
                ]
            ];

            return response()->json($response);

        } catch (\Exception $e) {
            Log::error('Personalized feed failed', [
                'user_id' => $request->user()?->id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'error' => 'Failed to generate personalized feed'
            ], 500);
        }
    }

    /**
     * Provide feedback on recommendations
     */
    public function feedback(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            if (!$user) {
                return response()->json(['error' => 'Unauthorized'], 401);
            }

            $validator = Validator::make($request->all(), [
                'recommendation_id' => 'required|string',
                'action' => 'required|string|in:click,like,dislike,share,ignore',
                'content_id' => 'required|string',
                'rating' => 'integer|min:1|max:5',
                'feedback_text' => 'string|max:500',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'error' => 'Validation failed',
                    'details' => $validator->errors()
                ], 422);
            }

            // Store feedback for improving recommendations
            $this->storeRecommendationFeedback($user->id, $request->all());

            return response()->json([
                'message' => 'Feedback recorded successfully',
                'feedback_id' => uniqid()
            ]);

        } catch (\Exception $e) {
            Log::error('Recommendation feedback failed', [
                'user_id' => $request->user()?->id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'error' => 'Failed to record feedback'
            ], 500);
        }
    }

    /**
     * Get recommendation analytics for admin
     */
    public function analytics(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            if (!$user || !$user->is_admin) {
                return response()->json(['error' => 'Unauthorized'], 403);
            }

            $timeframe = $request->get('timeframe', '7d');
            $analytics = $this->getRecommendationAnalytics($timeframe);

            return response()->json([
                'analytics' => $analytics,
                'timeframe' => $timeframe,
                'generated_at' => now()->toISOString(),
            ]);

        } catch (\Exception $e) {
            Log::error('Recommendation analytics failed', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'error' => 'Failed to get recommendation analytics'
            ], 500);
        }
    }

    /**
     * Get trending content
     */
    private function getTrendingContent(string $timeframe, int $limit): array
    {
        $hours = match($timeframe) {
            '24h' => 24,
            '7d' => 168,
            '30d' => 720,
            default => 24
        };

        // This would query the database for trending content
        // For now, return mock data
        return [
            [
                'id' => 'trending_1',
                'type' => 'bulletin_board',
                'title' => 'Trending Topic 1',
                'description' => 'Popular discussion topic',
                'engagement_score' => 0.95,
                'trending_since' => now()->subHours(2)->toISOString(),
            ],
            [
                'id' => 'trending_2',
                'type' => 'bulletin_board',
                'title' => 'Trending Topic 2',
                'description' => 'Another popular topic',
                'engagement_score' => 0.87,
                'trending_since' => now()->subHours(4)->toISOString(),
            ],
        ];
    }

    /**
     * Get personalized feed
     */
    private function getPersonalizedFeed(int $userId, int $perPage, int $offset): array
    {
        // This would combine recommendations with recent activity
        // For now, return mock data
        return [
            'items' => [
                [
                    'id' => 'feed_1',
                    'type' => 'recommendation',
                    'content' => 'Personalized recommendation 1',
                    'score' => 0.92,
                    'reason' => 'Based on your interests',
                ],
                [
                    'id' => 'feed_2',
                    'type' => 'activity',
                    'content' => 'Recent activity from your network',
                    'timestamp' => now()->subMinutes(30)->toISOString(),
                ],
            ],
            'total' => 50,
            'has_more' => true,
        ];
    }

    /**
     * Store recommendation feedback
     */
    private function storeRecommendationFeedback(int $userId, array $feedback): void
    {
        // This would store feedback in the database for ML model improvement
        Log::info('Recommendation feedback stored', [
            'user_id' => $userId,
            'feedback' => $feedback
        ]);
    }

    /**
     * Get recommendation analytics
     */
    private function getRecommendationAnalytics(string $timeframe): array
    {
        // This would return analytics about recommendation performance
        return [
            'total_recommendations' => 1250,
            'click_through_rate' => 0.23,
            'user_satisfaction' => 4.2,
            'top_performing_types' => ['ai', 'location'],
            'improvement_suggestions' => [
                'Increase diversity in content recommendations',
                'Improve location-based accuracy',
            ],
        ];
    }
}
