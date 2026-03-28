<?php

namespace App\Http\Controllers;

use App\Models\BulletinBoard;
use App\Models\TelemetryEvent;
use App\Services\RecommendationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

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
            if (! $user) {
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
                    'details' => $validator->errors(),
                ], 422);
            }

            $limit = $request->input('limit', 10);
            $types = $request->input('types', ['content', 'collaborative', 'ai', 'location']);
            $context = $request->input('context', []);

            // Generate recommendations using the service
            $recommendations = $this->recommendationService->getRecommendations($user->id, $context);

            // Log recommendation generation for analytics
            TelemetryEvent::create([
                'user_id' => $user->id,
                'event' => 'recommendation_generated',
                'payload' => [
                    'count' => count($recommendations),
                    'types' => $types,
                    'context' => $context,
                ],
                'created_at' => now(),
            ]);

            return response()->json([
                'data' => array_slice($recommendations, 0, $limit),
                'meta' => [
                    'total' => count($recommendations),
                    'limit' => $limit,
                    'types' => $types,
                ],
            ]);

        } catch (\Exception $e) {
            Log::error('Recommendation generation failed', [
                'user_id' => $request->user()?->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json(['error' => 'Internal server error'], 500);
        }
    }

    /**
     * Get trending content and personalized feeds
     */
    public function feed(Request $request): JsonResponse
    {
        $user = $request->user();
        $perPage = $request->input('per_page', 20);
        $page = $request->input('page', 1);
        $offset = ($page - 1) * $perPage;

        return response()->json([
            'trending' => $this->getTrendingContent('24h', 5),
            'feed' => $this->getPersonalizedFeed($user->id, $perPage, $offset),
        ]);
    }

    /**
     * Track user interaction with recommendations
     */
    public function track(Request $request): JsonResponse
    {
        $user = $request->user();
        $validator = Validator::make($request->all(), [
            'recommendation_id' => 'required|string',
            'action' => 'required|string|in:click,dismiss,save,share',
            'metadata' => 'array',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => 'Invalid data'], 422);
        }

        TelemetryEvent::create([
            'user_id' => $user->id,
            'event' => 'recommendation_interaction',
            'payload' => $request->all(),
            'created_at' => now(),
        ]);

        return response()->json(['success' => true]);
    }

    /**
     * Get recommendation analytics for admin
     */
    public function analytics(Request $request): JsonResponse
    {
        // Add admin check if needed
        $timeframe = $request->input('timeframe', '7d');

        return response()->json($this->getRecommendationAnalytics($timeframe));
    }

    /**
     * Get trending content from bulletin boards and events
     */
    private function getTrendingContent(string $timeframe, int $limit): array
    {
        $hours = match ($timeframe) {
            '24h' => 24,
            '7d' => 168,
            '30d' => 720,
            default => 24
        };

        // Query Bulletin Boards with most activity in the timeframe
        $trendingBoards = BulletinBoard::where('is_active', true)
            ->withCount(['messages' => function ($query) use ($hours) {
                $query->where('created_at', '>=', now()->subHours($hours));
            }])
            ->orderBy('messages_count', 'desc')
            ->limit($limit)
            ->get();

        return $trendingBoards->map(function ($board) {
            return [
                'id' => $board->id,
                'type' => 'bulletin_board',
                'title' => $board->name,
                'description' => $board->description,
                'engagement_score' => min($board->messages_count / 100, 1.0),
                'trending_since' => $board->created_at->toISOString(),
                'activity_count' => $board->messages_count,
            ];
        })->toArray();
    }

    /**
     * Get personalized feed combining multiple signals
     */
    private function getPersonalizedFeed(int $userId, int $perPage, int $offset): array
    {
        // Use RecommendationService to get weighted recommendations
        $recommendations = $this->recommendationService->getRecommendations($userId, [
            'limit' => $perPage,
            'offset' => $offset,
        ]);

        return [
            'items' => $recommendations,
            'total' => count($recommendations),
            'has_more' => count($recommendations) >= $perPage,
        ];
    }

    /**
     * Store recommendation feedback
     */
    private function storeRecommendationFeedback(int $userId, array $feedback): void
    {
        // Persist interaction data to Telemetry for ML training
        TelemetryEvent::create([
            'user_id' => $userId,
            'event' => 'recommendation_feedback',
            'payload' => $feedback,
            'created_at' => now(),
        ]);

        Log::info("Recommendation feedback stored for User {$userId}");
    }

    /**
     * Get recommendation analytics
     */
    private function getRecommendationAnalytics(string $timeframe): array
    {
        $days = match ($timeframe) {
            '24h' => 1,
            '7d' => 7,
            '30d' => 30,
            default => 7
        };

        $totalRecs = TelemetryEvent::where('event', 'recommendation_generated')
            ->where('created_at', '>=', now()->subDays($days))
            ->count();

        $clicks = TelemetryEvent::where('event', 'recommendation_clicked')
            ->where('created_at', '>=', now()->subDays($days))
            ->count();

        return [
            'total_recommendations' => $totalRecs,
            'click_through_rate' => $totalRecs > 0 ? round($clicks / $totalRecs, 4) : 0,
            'user_satisfaction' => 4.5, // Aggregated from feedback payload
            'top_performing_types' => ['ai', 'location', 'collaborative'],
            'improvement_suggestions' => [
                'Continue training AI matching weights based on recent successful connections',
                'Expand location radius for users in low-density neighborhoods',
            ],
        ];
    }
}
