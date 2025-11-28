<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\AdvancedRateLimitingService;
use Illuminate\Http\JsonResponse;
use OpenApi\Attributes as OA;

class RateLimitController extends Controller
{
    private AdvancedRateLimitingService $rateLimitingService;

    public function __construct(AdvancedRateLimitingService $rateLimitingService)
    {
        $this->rateLimitingService = $rateLimitingService;
    }

    /**
     * Get rate limit status for user
     * 
     * @OA\Get(
     *     path="/rate-limits/status/{action}",
     *     tags={"Rate Limiting"},
     *     summary="Get rate limit status",
     *     description="Check current rate limit status for a specific action",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="action", in="path", required=false, @OA\Schema(type="string", default="api_call")),
    *     @OA\Response(response=200, description="Rate limit status",
    *         @OA\JsonContent(ref="#/components/schemas/RateLimitStatusResponse")
    *     ),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized")
     * )
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
     * 
     * @OA\Get(
     *     path="/rate-limits/all-status",
     *     tags={"Rate Limiting"},
     *     summary="Get all rate limit statuses",
     *     description="Check rate limit status for all tracked actions",
     *     security={{"bearerAuth":{}}},
    *     @OA\Response(response=200, description="All rate limit statuses",
    *         @OA\JsonContent(ref="#/components/schemas/AllRateLimitStatusesResponse")
    *     ),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized")
     * )
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
     * 
     * @OA\Post(
     *     path="/rate-limits/reset/{action}",
     *     tags={"Rate Limiting"},
     *     summary="Reset rate limit",
     *     description="Reset rate limit for a specific action (admin only)",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="action", in="path", required=true, @OA\Schema(type="string")),
     *     @OA\Response(response=200, description="Rate limit reset"),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
    *     @OA\Response(response=403, ref="#/components/responses/Forbidden")
     * )
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
     * 
     * @OA\Get(
     *     path="/rate-limits/stats/{timeframe}",
     *     tags={"Rate Limiting"},
     *     summary="Get rate limit statistics",
     *     description="Retrieve rate limiting statistics for a timeframe (admin only)",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="timeframe", in="path", required=false, @OA\Schema(type="string", default="1h", example="1h")),
     *     @OA\Response(response=200, description="Rate limit statistics"),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
    *     @OA\Response(response=403, ref="#/components/responses/Forbidden")
     * )
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
     * 
     * @OA\Get(
     *     path="/rate-limits/suspicious-activity",
     *     tags={"Rate Limiting"},
     *     summary="Check suspicious activity",
     *     description="Detect suspicious rate limit patterns for the authenticated user",
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(response=200, description="Suspicious activity check",
     *         @OA\JsonContent(
     *             @OA\Property(property="user_id", type="integer"),
     *             @OA\Property(property="suspicious_activity", type="object"),
     *             @OA\Property(property="timestamp", type="string", format="date-time")
     *         )
     *     ),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized")
     * )
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
     * 
     * @OA\Post(
     *     path="/rate-limits/cleanup",
     *     tags={"Rate Limiting"},
     *     summary="Clean up expired entries",
     *     description="Remove expired rate limit entries from storage (admin only)",
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(response=200, description="Cleanup completed",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean"),
     *             @OA\Property(property="cleaned_entries", type="integer"),
     *             @OA\Property(property="message", type="string")
     *         )
     *     ),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
    *     @OA\Response(response=403, ref="#/components/responses/Forbidden")
     * )
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
