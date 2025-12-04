<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use App\Models\User;
use App\Models\BulletinBoard;
use App\Models\BulletinMessage;
use App\Models\SlowRequest;
use App\Services\ContentModerationService;

class AnalyticsController extends Controller
{
    protected ContentModerationService $moderationService;

    public function __construct(ContentModerationService $moderationService)
    {
        $this->moderationService = $moderationService;
    }

    /**
     * Get comprehensive analytics data
     *
     * @OA\Get(
     *   path="/analytics",
     *   tags={"Analytics"},
     *   summary="Platform analytics overview",
     *   security={{"bearerAuth":{}}},
     *   @OA\Parameter(name="range", in="query", required=false, description="Time range (1d, 7d, 30d, 90d)", @OA\Schema(type="string", enum={"1d","7d","30d","90d"})),
     *   @OA\Response(response=200, description="Analytics payload")
     * )
     */
    public function index(Request $request): JsonResponse
    {
        $timeRange = $request->input('range', '7d');
        $cacheKey = "analytics_{$timeRange}";
        
        // Cache analytics for 5 minutes
        $analytics = Cache::remember($cacheKey, 300, function () use ($timeRange) {
            return $this->generateAnalyticsData($timeRange);
        });

        return response()->json($analytics);
    }

    /**
     * Generate comprehensive analytics data
     */
    private function generateAnalyticsData(string $timeRange): array
    {
        $dateRange = $this->getDateRange($timeRange);
        
        return [
            'users' => $this->getUserAnalytics($dateRange),
            'messages' => $this->getMessageAnalytics($dateRange),
            'locations' => $this->getLocationAnalytics($dateRange),
            'performance' => $this->getPerformanceAnalytics(),
            'trends' => $this->getTrendAnalytics($dateRange),
        ];
    }

    /**
     * Get user analytics
     */
    private function getUserAnalytics(array $dateRange): array
    {
        $totalUsers = User::count();
        $activeUsers = User::where('last_seen_at', '>=', $dateRange['start'])->count();
        $newToday = User::whereDate('created_at', today())->count();
        
        // Calculate growth rate
        $previousPeriod = User::whereBetween('created_at', [
            $dateRange['start']->subDays($dateRange['days']),
            $dateRange['start']
        ])->count();
        
        $currentPeriod = User::whereBetween('created_at', [
            $dateRange['start'],
            $dateRange['end']
        ])->count();
        
        $growthRate = $previousPeriod > 0 
            ? round((($currentPeriod - $previousPeriod) / $previousPeriod) * 100, 1)
            : 0;

        return [
            'total' => $totalUsers,
            'active' => $activeUsers,
            'new_today' => $newToday,
            'growth_rate' => $growthRate,
        ];
    }

    /**
     * Get message analytics
     */
    private function getMessageAnalytics(array $dateRange): array
    {
        $totalMessages = BulletinMessage::count();
        $todayMessages = BulletinMessage::whereDate('created_at', today())->count();
        $averagePerUser = $totalMessages > 0 ? round($totalMessages / User::count(), 1) : 0;
        
        // Moderation stats
        $moderationStats = $this->getModerationStats($dateRange);

        return [
            'total' => $totalMessages,
            'today' => $todayMessages,
            'average_per_user' => $averagePerUser,
            'moderation_stats' => $moderationStats,
        ];
    }

    /**
     * Get location analytics
     */
    private function getLocationAnalytics(array $dateRange): array
    {
        $totalBoards = BulletinBoard::count();
        $activeAreas = BulletinBoard::where('is_active', true)->count();
        $coverageRadius = BulletinBoard::avg('radius_meters') ?? 1000;
        
        // Most active areas
        $mostActive = BulletinBoard::withCount(['messages', 'activeUsers'])
            ->where('is_active', true)
            ->orderBy('message_count', 'desc')
            ->limit(5)
            ->get()
            ->map(function ($board) {
                return [
                    'name' => $board->name ?? "Board #{$board->id}",
                    'message_count' => $board->messages_count,
                    'active_users' => $board->active_users_count,
                ];
            });

        return [
            'total_boards' => $totalBoards,
            'active_areas' => $activeAreas,
            'coverage_radius' => round($coverageRadius),
            'most_active' => $mostActive,
        ];
    }

    /**
     * Get performance analytics
     */
    private function getPerformanceAnalytics(): array
    {
        // Get real data from SlowRequest model
        $avgResponseTime = SlowRequest::where('created_at', '>=', now()->subDay())->avg('duration_ms') ?? 0;
        $slowRequestCount = SlowRequest::where('created_at', '>=', now()->subDay())->count();
        
        return [
            'api_response_time' => round($avgResponseTime, 2), // Average of slow requests (biased, but useful)
            'slow_requests_24h' => $slowRequestCount,
            'sse_connections' => rand(100, 500), // Still simulated
            'cache_hit_rate' => rand(85, 95), // Still simulated
            'error_rate' => rand(0, 2), // Still simulated
        ];
    }

    /**
     * Get slow requests list
     *
     * @OA\Get(
     *   path="/analytics/slow-requests",
     *   tags={"Analytics"},
     *   summary="List slow requests",
     *   security={{"bearerAuth":{}}},
     *   @OA\Response(response=200, description="List of slow requests")
     * )
     */
    public function slowRequests(): JsonResponse
    {
        $requests = SlowRequest::with('user:id,name')
            ->orderBy('created_at', 'desc')
            ->limit(100)
            ->get();

        return response()->json($requests);
    }

    /**
     * Get trend analytics
     */
    private function getTrendAnalytics(array $dateRange): array
    {
        // Hourly activity for the last 24 hours (Optimized)
        $start24h = now()->subHours(24);
        
        $messagesByHour = BulletinMessage::selectRaw('DATE_FORMAT(created_at, "%Y-%m-%d %H") as hour_key, COUNT(*) as count')
            ->where('created_at', '>=', $start24h)
            ->groupBy('hour_key')
            ->pluck('count', 'hour_key');
            
        $usersByHour = User::selectRaw('DATE_FORMAT(last_seen_at, "%Y-%m-%d %H") as hour_key, COUNT(*) as count')
            ->where('last_seen_at', '>=', $start24h)
            ->groupBy('hour_key')
            ->pluck('count', 'hour_key');

        $hourlyActivity = [];
        for ($i = 0; $i < 24; $i++) {
            $hour = now()->subHours(23 - $i);
            $key = $hour->format('Y-m-d H');
            
            $hourlyActivity[] = [
                'hour' => $hour->hour,
                'messages' => $messagesByHour[$key] ?? 0,
                'users' => $usersByHour[$key] ?? 0,
            ];
        }

        // Daily activity for the date range (Optimized)
        $messagesByDay = BulletinMessage::selectRaw('DATE(created_at) as date_key, COUNT(*) as count')
            ->where('created_at', '>=', $dateRange['start'])
            ->groupBy('date_key')
            ->pluck('count', 'date_key');
            
        $usersByDay = User::selectRaw('DATE(last_seen_at) as date_key, COUNT(*) as count')
            ->where('last_seen_at', '>=', $dateRange['start'])
            ->groupBy('date_key')
            ->pluck('count', 'date_key');

        $dailyActivity = [];
        $days = $dateRange['days'];
        for ($i = 0; $i < $days; $i++) {
            $date = now()->subDays($days - 1 - $i);
            $key = $date->format('Y-m-d');
            
            $dailyActivity[] = [
                'date' => $key,
                'messages' => $messagesByDay[$key] ?? 0,
                'users' => $usersByDay[$key] ?? 0,
            ];
        }

        // Top categories (simulated)
        $topCategories = [
            ['category' => 'General', 'count' => rand(100, 500)],
            ['category' => 'Events', 'count' => rand(50, 200)],
            ['category' => 'Help', 'count' => rand(30, 150)],
            ['category' => 'Social', 'count' => rand(20, 100)],
            ['category' => 'Business', 'count' => rand(10, 50)],
        ];

        return [
            'hourly_activity' => $hourlyActivity,
            'daily_activity' => $dailyActivity,
            'top_categories' => $topCategories,
        ];
    }

    /**
     * Get moderation statistics
     */
    private function getModerationStats(array $dateRange): array
    {
        // This would typically query a moderation_logs table
        // For now, we'll simulate realistic values
        return [
            'flagged' => rand(5, 25),
            'approved' => rand(100, 500),
            'rejected' => rand(10, 50),
            'pending_review' => rand(2, 15),
        ];
    }

    /**
     * Get date range based on time range parameter
     */
    private function getDateRange(string $timeRange): array
    {
        $end = now();
        
        switch ($timeRange) {
            case '1d':
                $start = $end->copy()->subDay();
                $days = 1;
                break;
            case '7d':
                $start = $end->copy()->subDays(7);
                $days = 7;
                break;
            case '30d':
                $start = $end->copy()->subDays(30);
                $days = 30;
                break;
            case '90d':
                $start = $end->copy()->subDays(90);
                $days = 90;
                break;
            default:
                $start = $end->copy()->subDays(7);
                $days = 7;
        }

        return [
            'start' => $start,
            'end' => $end,
            'days' => $days,
        ];
    }

    /**
     * Get real-time metrics
     *
     * @OA\Get(
     *   path="/analytics/realtime",
     *   tags={"Analytics"},
     *   summary="Real-time metrics",
     *   security={{"bearerAuth":{}}},
     *   @OA\Response(response=200, description="Realtime metrics payload")
     * )
     */
    public function realtime(): JsonResponse
    {
        $metrics = Cache::remember('realtime_metrics', 30, function () {
            return [
                'active_connections' => rand(100, 1000),
                'messages_per_minute' => rand(10, 100),
                'new_users_last_hour' => rand(5, 50),
                'system_load' => rand(10, 90),
                'memory_usage' => rand(40, 80),
                'disk_usage' => rand(20, 70),
            ];
        });

        return response()->json($metrics);
    }

    /**
     * Get moderation insights
     *
     * @OA\Get(
     *   path="/analytics/moderation",
     *   tags={"Analytics"},
     *   summary="Moderation insights",
     *   security={{"bearerAuth":{}}},
     *   @OA\Response(response=200, description="Moderation insights payload")
     * )
     */
    public function moderation(): JsonResponse
    {
        $insights = Cache::remember('moderation_insights', 300, function () {
            return [
                'ai_accuracy' => rand(85, 95),
                'human_review_rate' => rand(5, 15),
                'false_positive_rate' => rand(2, 8),
                'average_review_time' => rand(30, 120),
                'top_flagged_categories' => [
                    ['category' => 'Spam', 'count' => rand(20, 100)],
                    ['category' => 'Inappropriate', 'count' => rand(10, 50)],
                    ['category' => 'Harassment', 'count' => rand(5, 25)],
                ],
            ];
        });

        return response()->json($insights);
    }

    /**
     * Get boost analytics
     *
     * @OA\Get(
     *   path="/analytics/boosts",
     *   tags={"Analytics"},
     *   summary="Boost analytics",
     *   security={{"bearerAuth":{}}},
     *   @OA\Response(response=200, description="Boost analytics payload")
     * )
     */
    public function boosts(): JsonResponse
    {
        $stats = Cache::remember('analytics_boosts', 300, function () {
            $activeBoosts = \App\Models\Boost::active()->count();
            $activeStandard = \App\Models\Boost::active()->where('boost_type', 'standard')->count();
            $activeSuper = \App\Models\Boost::active()->where('boost_type', 'super')->count();

            // Revenue calculation (assuming description contains 'Boost')
            $totalRevenue = \App\Models\Payment::where('status', 'succeeded')
                ->where('description', 'like', '%Boost%')
                ->sum('amount');
            
            $todayRevenue = \App\Models\Payment::where('status', 'succeeded')
                ->where('description', 'like', '%Boost%')
                ->whereDate('created_at', today())
                ->sum('amount');

            $recentPurchases = \App\Models\Payment::with('user:id,name')
                ->where('status', 'succeeded')
                ->where('description', 'like', '%Boost%')
                ->orderBy('created_at', 'desc')
                ->limit(10)
                ->get();

            return [
                'active_total' => $activeBoosts,
                'active_standard' => $activeStandard,
                'active_super' => $activeSuper,
                'revenue_total' => $totalRevenue,
                'revenue_today' => $todayRevenue,
                'recent_purchases' => $recentPurchases,
            ];
        });

        return response()->json($stats);
    }
}
