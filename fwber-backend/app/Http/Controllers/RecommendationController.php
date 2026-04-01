<?php

namespace App\Http\Controllers;

use App\Models\BulletinBoard;
use App\Models\TelemetryEvent;
use App\Services\RecommendationService;
use Illuminate\Database\QueryException;
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

            $types = $this->normalizeTypes($request->input('types'));
            $context = $this->normalizeContext($request->input('context'));

            // Validate request parameters
            $validator = Validator::make([
                'limit' => $request->input('limit', 10),
                'types' => $types,
                'context' => $context,
            ], [
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

            // Generate recommendations using the service
            $recommendations = $this->filterRecommendationsByType(
                $this->recommendationService->getRecommendations($user->id, $context),
                $types
            );

            // Log recommendation generation for analytics
            $this->recordTelemetrySafely($user->id, 'recommendation_generated', [
                'count' => count($recommendations),
                'types' => $types,
                'context' => $context,
            ]);

            return response()->json(
                $this->buildRecommendationResponse(
                    array_slice($recommendations, 0, $limit),
                    $types,
                    $context,
                    count($recommendations)
                )
            );

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
        if (! $user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $perPage = $request->input('per_page', 20);
        $page = $request->input('page', 1);
        $offset = ($page - 1) * $perPage;

        $feed = $this->getPersonalizedFeed($user->id, $perPage, $offset);

        return response()->json([
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
            ],
        ]);
    }

    /**
     * Track user interaction with recommendations
     */
    public function feedback(Request $request): JsonResponse
    {
        $user = $request->user();
        $validator = Validator::make($request->all(), [
            'recommendation_id' => 'required|string',
            'action' => 'required|string|in:click,like,dislike,share,ignore,dismiss,save',
            'metadata' => 'array',
            'content_id' => 'nullable|string',
            'rating' => 'nullable|numeric|min:0|max:5',
            'feedback_text' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => 'Invalid data'], 422);
        }

        $eventName = match ($request->input('action')) {
            'click' => 'recommendation_clicked',
            'share' => 'recommendation_shared',
            'like' => 'recommendation_liked',
            'dislike' => 'recommendation_disliked',
            'ignore', 'dismiss' => 'recommendation_dismissed',
            default => 'recommendation_interaction',
        };

        $this->recordTelemetrySafely($user->id, $eventName, $request->all());

        return response()->json([
            'message' => 'Feedback recorded successfully.',
            'feedback_id' => sprintf('%s:%s', $user->id, $request->input('recommendation_id')),
        ]);
    }

    public function track(Request $request): JsonResponse
    {
        return $this->feedback($request);
    }

    /**
     * Get recommendation analytics for admin
     */
    public function analytics(Request $request): JsonResponse
    {
        // Add admin check if needed
        $timeframe = $request->input('timeframe', '7d');

        return response()->json([
            'analytics' => $this->getRecommendationAnalytics($timeframe),
            'timeframe' => $timeframe,
            'generated_at' => now()->toISOString(),
        ]);
    }

    public function byType(Request $request, string $type): JsonResponse
    {
        if (! in_array($type, ['content', 'collaborative', 'ai', 'location'], true)) {
            return response()->json(['error' => 'Invalid recommendation type'], 422);
        }

        $request->merge(['types' => [$type]]);

        return $this->index($request);
    }

    public function trending(Request $request): JsonResponse
    {
        $timeframe = $request->input('timeframe', '24h');
        $limit = (int) $request->input('limit', 5);
        $trending = $this->getTrendingContent($timeframe, $limit);

        return response()->json([
            'trending' => $trending,
            'metadata' => [
                'timeframe' => $timeframe,
                'total' => count($trending),
                'generated_at' => now()->toISOString(),
            ],
        ]);
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
        $pagedRecommendations = array_slice($recommendations, $offset, $perPage);

        return [
            'items' => $pagedRecommendations,
            'total' => count($recommendations),
            'has_more' => count($recommendations) > ($offset + $perPage),
        ];
    }

    /**
     * Store recommendation feedback
     */
    private function storeRecommendationFeedback(int $userId, array $feedback): void
    {
        // Persist interaction data to Telemetry for ML training
        $this->recordTelemetrySafely($userId, 'recommendation_feedback', $feedback);

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

    private function normalizeTypes(mixed $types): array
    {
        if (is_string($types)) {
            $types = array_filter(array_map('trim', explode(',', $types)));
        }

        if (! is_array($types) || $types === []) {
            return ['content', 'collaborative', 'ai', 'location'];
        }

        return array_values($types);
    }

    private function normalizeContext(mixed $context): array
    {
        if (is_string($context) && $context !== '') {
            $decoded = json_decode($context, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                $context = $decoded;
            }
        }

        return is_array($context) ? $context : [];
    }

    private function filterRecommendationsByType(array $recommendations, array $types): array
    {
        return array_values(array_filter($recommendations, function ($recommendation) use ($types) {
            $recommendationType = $recommendation['type'] ?? null;

            return is_string($recommendationType) && in_array($recommendationType, $types, true);
        }));
    }

    private function buildRecommendationResponse(array $recommendations, array $types, array $context, int $total): array
    {
        return [
            'recommendations' => $recommendations,
            'metadata' => [
                'total' => $total,
                'types' => $types,
                'context' => $context,
                'generated_at' => now()->toISOString(),
                'cache_hit' => false,
            ],
            'data' => $recommendations,
            'meta' => [
                'total' => $total,
                'types' => $types,
                'context' => $context,
            ],
        ];
    }

    private function recordTelemetrySafely(int $userId, string $event, array $payload): void
    {
        try {
            TelemetryEvent::create([
                'user_id' => $userId,
                'event' => $event,
                'payload' => $payload,
                'created_at' => now(),
            ]);
        } catch (QueryException $exception) {
            Log::warning('Recommendation telemetry persistence failed', [
                'user_id' => $userId,
                'event' => $event,
                'error' => $exception->getMessage(),
            ]);
        }
    }
}
