<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\AdvancedRateLimitingService;
use Illuminate\Http\JsonResponse;

class RateLimitController extends Controller
{
    private AdvancedRateLimitingService $rateLimitingService;

    public function __construct(AdvancedRateLimitingService $rateLimitingService)
    {
        $this->rateLimitingService = $rateLimitingService;
    }

    /**
     * Get rate limit status for user
     */
    public function getStatus(Request $request, string $action = 'api_call'): JsonResponse
    {
        $userId = $request->user()->id;
        $status = $this->rateLimitingService->getRateLimitStatus($userId, $action);

        return response()->json([
            'action' => $action,
            'status' => $status,
            'timestamp' => now()->toISOString(),
        ]);
    }

    /**
     * Get rate limit status for all actions
     */
    public function getAllStatus(Request $request): JsonResponse
    {
        $userId = $request->user()->id;
        $actions = ['content_generation', 'bulletin_post', 'location_update', 'photo_upload', 'api_call'];
        
        $statuses = [];
        foreach ($actions as $action) {
            $statuses[$action] = $this->rateLimitingService->getRateLimitStatus($userId, $action);
        }

        return response()->json([
            'user_id' => $userId,
            'statuses' => $statuses,
            'timestamp' => now()->toISOString(),
        ]);
    }

    /**
     * Reset rate limit for user (admin only)
     */
    public function reset(Request $request, string $action): JsonResponse
    {
        $userId = $request->user()->id;
        $success = $this->rateLimitingService->resetRateLimit($userId, $action);

        return response()->json([
            'success' => $success,
            'message' => $success ? 'Rate limit reset successfully' : 'Failed to reset rate limit',
            'action' => $action,
            'timestamp' => now()->toISOString(),
        ]);
    }

    /**
     * Get rate limit statistics (admin only)
     */
    public function getStats(Request $request, string $timeframe = '1h'): JsonResponse
    {
        $stats = $this->rateLimitingService->getRateLimitStats($timeframe);

        return response()->json([
            'timeframe' => $timeframe,
            'statistics' => $stats,
            'timestamp' => now()->toISOString(),
        ]);
    }

    /**
     * Check for suspicious activity
     */
    public function checkSuspiciousActivity(Request $request): JsonResponse
    {
        $userId = $request->user()->id;
        $suspiciousActivity = $this->rateLimitingService->detectSuspiciousActivity($userId);

        return response()->json([
            'user_id' => $userId,
            'suspicious_activity' => $suspiciousActivity,
            'timestamp' => now()->toISOString(),
        ]);
    }

    /**
     * Clean up expired rate limit entries (admin only)
     */
    public function cleanup(Request $request): JsonResponse
    {
        $cleaned = $this->rateLimitingService->cleanupExpiredEntries();

        return response()->json([
            'success' => true,
            'cleaned_entries' => $cleaned,
            'message' => "Cleaned up {$cleaned} expired rate limit entries",
            'timestamp' => now()->toISOString(),
        ]);
    }
}
