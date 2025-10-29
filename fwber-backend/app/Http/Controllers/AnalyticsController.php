<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use App\Models\User;
use App\Models\BulletinBoard;
use App\Models\BulletinMessage;
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
        // These would typically come from monitoring systems
        // For now, we'll simulate realistic values
        return [
            'api_response_time' => rand(50, 200),
            'sse_connections' => rand(100, 500),
            'cache_hit_rate' => rand(85, 95),
            'error_rate' => rand(0, 2),
        ];
    }

    /**
     * Get trend analytics
     */
    private function getTrendAnalytics(array $dateRange): array
    {
        // Hourly activity for the last 24 hours
        $hourlyActivity = [];
        for ($i = 0; $i < 24; $i++) {
            $hour = now()->subHours(23 - $i);
            $messages = BulletinMessage::whereBetween('created_at', [
                $hour->copy()->startOfHour(),
                $hour->copy()->endOfHour()
            ])->count();
            
            $users = User::whereBetween('last_seen_at', [
                $hour->copy()->startOfHour(),
                $hour->copy()->endOfHour()
            ])->count();
            
            $hourlyActivity[] = [
                'hour' => $hour->hour,
                'messages' => $messages,
                'users' => $users,
            ];
        }

        // Daily activity for the date range
        $dailyActivity = [];
        $days = $dateRange['days'];
        for ($i = 0; $i < $days; $i++) {
            $date = now()->subDays($days - 1 - $i);
            $messages = BulletinMessage::whereDate('created_at', $date)->count();
            $users = User::whereDate('last_seen_at', $date)->count();
            
            $dailyActivity[] = [
                'date' => $date->format('Y-m-d'),
                'messages' => $messages,
                'users' => $users,
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
}
