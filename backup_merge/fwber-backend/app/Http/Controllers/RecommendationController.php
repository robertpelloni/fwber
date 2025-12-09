<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Services\RecommendationService;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use OpenApi\Attributes as OA;

use App\Models\Boost;

class RecommendationController extends Controller
{
    private RecommendationService $recommendationService;

    public function __construct(RecommendationService $recommendationService)
    {
        $this->recommendationService = $recommendationService;
    }

    /**
     * Get personalized recommendations for the authenticated user
     *
     * @OA\Get(
     *     path="/recommendations",
     *     summary="Get personalized recommendations",
     *     description="Returns AI-powered recommendations tailored to the user's preferences, location, and behavior.",
     *     operationId="getRecommendations",
     *     tags={"Recommendations"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="limit",
     *         in="query",
     *         description="Maximum number of recommendations to return",
     *         required=false,
     *         @OA\Schema(type="integer", minimum=1, maximum=50, default=10)
     *     ),
     *     @OA\Parameter(
     *         name="types",
     *         in="query",
     *         description="Recommendation types to include",
     *         required=false,
     *         @OA\Schema(type="array", @OA\Items(type="string", enum={"content","collaborative","ai","location"}))
     *     ),
     *     @OA\Parameter(
     *         name="context[latitude]",
     *         in="query",
     *         description="User latitude for location-based recommendations",
     *         required=false,
     *         @OA\Schema(type="number", format="float", minimum=-90, maximum=90)
     *     ),
     *     @OA\Parameter(
     *         name="context[longitude]",
     *         in="query",
     *         description="User longitude for location-based recommendations",
     *         required=false,
     *         @OA\Schema(type="number", format="float", minimum=-180, maximum=180)
     *     ),
     *     @OA\Parameter(
     *         name="context[radius]",
     *         in="query",
     *         description="Radius in meters for location-based recommendations",
     *         required=false,
     *         @OA\Schema(type="integer", minimum=100, maximum=50000)
     *     ),
    *     @OA\Response(
    *         response=200,
    *         description="Recommendations retrieved successfully",
    *         @OA\JsonContent(ref="#/components/schemas/RecommendationList")
    *     ),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=422, ref="#/components/responses/ValidationError")
     * )
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

            // Generate cache key
            $cacheKey = "recommendations:user:{$user->id}:" . md5(json_encode([
                'limit' => $limit,
                'types' => $types,
                'context' => $context
            ]));

            // Try to get from cache (10 minutes TTL)
            $filteredRecommendations = Cache::remember($cacheKey, 600, function () use ($user, $context, $types, $limit) {
                // Get recommendations
                $recommendations = $this->recommendationService->getRecommendations($user->id, $context);

                // Filter by requested types
                $filteredRecommendations = array_filter($recommendations, function($rec) use ($types) {
                    return in_array($rec['type'], $types);
                });

                // Fetch active boosts to prioritize
                $boostedUsers = Boost::active()
                    ->where('user_id', '!=', $user->id) // Don't recommend self
                    ->with('user')
                    ->get();

                $boostedRecommendations = [];
                foreach ($boostedUsers as $boost) {
                    $boostedRecommendations[] = [
                        'type' => 'boosted_profile',
                        'content' => $boost->user,
                        'score' => 2.0, // High score to ensure top placement
                        'reason' => 'Popular profile',
                        'is_boosted' => true
                    ];
                }

                // Merge boosted profiles at the top
                $filteredRecommendations = array_merge($boostedRecommendations, $filteredRecommendations);

                // Apply limit
                return array_slice($filteredRecommendations, 0, $limit);
            });

            // Add metadata
            $response = [
                'recommendations' => $filteredRecommendations,
                'metadata' => [
                    'total' => count($filteredRecommendations),
                    'types' => $types,
                    'context' => $context,
                    'generated_at' => now()->toISOString(),
                    'cache_hit' => Cache::has($cacheKey),
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
     *
     * @OA\Get(
     *     path="/recommendations/type/{type}",
     *     summary="Get recommendations by type",
     *     description="Returns recommendations filtered by a specific recommendation type.",
     *     operationId="getRecommendationsByType",
     *     tags={"Recommendations"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="type",
     *         in="path",
     *         description="Recommendation type",
     *         required=true,
     *         @OA\Schema(type="string", enum={"content","collaborative","ai","location"})
     *     ),
     *     @OA\Parameter(
     *         name="limit",
     *         in="query",
     *         description="Maximum number of recommendations to return",
     *         required=false,
     *         @OA\Schema(type="integer", minimum=1, maximum=50, default=10)
     *     ),
     *     @OA\Response(response=200, description="Recommendations retrieved successfully"),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=422, ref="#/components/responses/ValidationError")
     * )
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
     *
     * @OA\Get(
     *     path="/recommendations/trending",
     *     summary="Get trending recommendations",
     *     description="Returns recommendations for trending content based on recent activity.",
     *     operationId="getTrendingRecommendations",
     *     tags={"Recommendations"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="limit",
     *         in="query",
     *         description="Maximum number of trending items",
     *         required=false,
     *         @OA\Schema(type="integer", minimum=1, maximum=50, default=10)
     *     ),
     *     @OA\Parameter(
     *         name="timeframe",
     *         in="query",
     *         description="Timeframe for trending calculation",
     *         required=false,
     *         @OA\Schema(type="string", enum={"24h","7d","30d"}, default="24h")
     *     ),
    *     @OA\Response(
    *         response=200,
    *         description="Trending recommendations retrieved successfully",
    *         @OA\JsonContent(ref="#/components/schemas/TrendingList")
    *     ),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized")
     * )
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

            // Generate cache key
            $cacheKey = "recommendations:trending:{$timeframe}:{$limit}";
            
            // Try to get from cache (30 minutes TTL)
            $trendingContent = Cache::remember($cacheKey, 1800, function () use ($timeframe, $limit) {
                return $this->getTrendingContent($timeframe, $limit);
            });

            $response = [
                'trending' => $trendingContent,
                'metadata' => [
                    'timeframe' => $timeframe,
                    'total' => count($trendingContent),
                    'generated_at' => now()->toISOString(),
                    'cache_hit' => Cache::has($cacheKey), // Note: This check might be slightly off if just cached, but good enough for metadata
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
     *
     * @OA\Get(
     *     path="/recommendations/feed",
     *     summary="Get personalized feed",
     *     description="Returns a personalized feed combining recommendations and recent activity.",
     *     operationId="getPersonalizedFeed",
     *     tags={"Recommendations"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="page",
     *         in="query",
     *         description="Page number",
     *         required=false,
     *         @OA\Schema(type="integer", minimum=1, default=1)
     *     ),
     *     @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         description="Items per page",
     *         required=false,
     *         @OA\Schema(type="integer", minimum=1, maximum=50, default=20)
     *     ),
    *     @OA\Response(
    *         response=200,
    *         description="Personalized feed retrieved successfully",
    *         @OA\JsonContent(ref="#/components/schemas/FeedResponse")
    *     ),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized")
     * )
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

            // Generate cache key
            $cacheKey = "recommendations:feed:user:{$user->id}:page:{$page}:limit:{$perPage}";

            // Try to get from cache (5 minutes TTL)
            $feed = Cache::remember($cacheKey, 300, function () use ($user, $perPage, $offset) {
                return $this->getPersonalizedFeed($user->id, $perPage, $offset);
            });

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
                    'cache_hit' => Cache::has($cacheKey),
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
     *
     * @OA\Post(
     *     path="/recommendations/feedback",
     *     summary="Submit recommendation feedback",
     *     description="Submits feedback on a recommendation to improve future recommendations.",
     *     operationId="submitRecommendationFeedback",
     *     tags={"Recommendations"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"recommendation_id","action","content_id"},
     *             @OA\Property(property="recommendation_id", type="string"),
     *             @OA\Property(property="action", type="string", enum={"click","like","dislike","share","ignore"}),
     *             @OA\Property(property="content_id", type="string"),
     *             @OA\Property(property="rating", type="integer", minimum=1, maximum=5),
     *             @OA\Property(property="feedback_text", type="string", maxLength=500)
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Feedback recorded successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string"),
     *             @OA\Property(property="feedback_id", type="string")
     *         )
     *     ),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=422, ref="#/components/responses/ValidationError")
     * )
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
     *
     * @OA\Get(
     *     path="/recommendations/analytics",
     *     summary="Get recommendation analytics",
     *     description="Returns analytics about recommendation system performance. Admin only.",
     *     operationId="getRecommendationAnalytics",
     *     tags={"Recommendations"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="timeframe",
     *         in="query",
     *         description="Timeframe for analytics",
     *         required=false,
     *         @OA\Schema(type="string", enum={"24h","7d","30d"}, default="7d")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Analytics retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="analytics", type="object",
     *                 @OA\Property(property="total_recommendations", type="integer"),
     *                 @OA\Property(property="click_through_rate", type="number", format="float"),
     *                 @OA\Property(property="user_satisfaction", type="number", format="float"),
     *                 @OA\Property(property="top_performing_types", type="array", @OA\Items(type="string")),
     *                 @OA\Property(property="improvement_suggestions", type="array", @OA\Items(type="string"))
     *             ),
     *             @OA\Property(property="timeframe", type="string"),
     *             @OA\Property(property="generated_at", type="string", format="date-time")
     *         )
     *     ),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
    *     @OA\Response(response=403, ref="#/components/responses/Forbidden")
     * )
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
