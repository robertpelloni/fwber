<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Cache;

class HealthController extends Controller
{
    /**
     * Comprehensive health check endpoint.
     * Returns 200 if all systems healthy, 503 if any critical service is down.
    *
    * @OA\Get(
    *   path="/health",
    *   tags={"Health"},
    *   summary="Comprehensive health check",
    *   description="Checks database, Redis, cache, storage and queue configuration. Returns 503 if any critical service is down.",
    *   @OA\Response(
    *     response=200,
    *     description="All systems healthy",
    *     @OA\JsonContent(
    *       type="object",
    *       @OA\Property(property="status", type="string", example="healthy"),
    *       @OA\Property(property="timestamp", type="string", format="date-time"),
    *       @OA\Property(property="version", type="string", example="1.0.0"),
    *       @OA\Property(property="environment", type="string", example="local"),
    *       @OA\Property(property="checks", type="object"),
    *       @OA\Property(property="metrics", type="object")
    *     )
    *   ),
    *   @OA\Response(
    *     response=503,
    *     description="One or more services unhealthy",
    *     @OA\JsonContent(
    *       type="object",
    *       @OA\Property(property="status", type="string", example="unhealthy")
    *     )
    *   )
    * )
     */
    public function check(): JsonResponse
    {
        $status = [
            'status' => 'healthy',
            'timestamp' => now()->toIso8601String(),
            'version' => config('app.version', '1.0.0'),
            'environment' => config('app.env'),
            'checks' => [],
        ];

        // Database connectivity
        try {
            DB::connection()->getPdo();
            $dbVersion = DB::select('SELECT VERSION() as version')[0]->version ?? 'unknown';
            $status['checks']['database'] = [
                'status' => 'ok',
                'version' => $dbVersion,
                'connection' => DB::connection()->getDatabaseName(),
            ];
        } catch (\Exception $e) {
            $status['checks']['database'] = [
                'status' => 'failed',
                'error' => $e->getMessage(),
            ];
            $status['status'] = 'unhealthy';
        }

        // Redis connectivity
        try {
            Redis::ping();
            $redisInfo = Redis::info('server');
            $status['checks']['redis'] = [
                'status' => 'ok',
                'version' => $redisInfo['redis_version'] ?? 'unknown',
            ];
        } catch (\Exception $e) {
            $status['checks']['redis'] = [
                'status' => 'failed',
                'error' => $e->getMessage(),
            ];
            $status['status'] = 'unhealthy';
        }

        // Cache functionality
        try {
            $testKey = 'health_check_' . time();
            Cache::put($testKey, 'test', 10);
            $retrieved = Cache::get($testKey);
            Cache::forget($testKey);
            
            $status['checks']['cache'] = [
                'status' => $retrieved === 'test' ? 'ok' : 'failed',
                'driver' => config('cache.default'),
            ];
        } catch (\Exception $e) {
            $status['checks']['cache'] = [
                'status' => 'failed',
                'error' => $e->getMessage(),
            ];
        }

        // Storage writability
        $storagePath = storage_path('logs');
        $status['checks']['storage'] = [
            'status' => is_writable($storagePath) ? 'ok' : 'failed',
            'path' => $storagePath,
            'writable' => is_writable($storagePath),
        ];

        if (!is_writable($storagePath)) {
            $status['status'] = 'unhealthy';
        }

        // Queue connectivity (optional, non-critical)
        try {
            $queueConnection = config('queue.default');
            $status['checks']['queue'] = [
                'status' => 'ok',
                'connection' => $queueConnection,
            ];
        } catch (\Exception $e) {
            $status['checks']['queue'] = [
                'status' => 'degraded',
                'error' => $e->getMessage(),
            ];
        }

        // Application metrics
        $status['metrics'] = [
            'memory_usage' => round(memory_get_usage(true) / 1024 / 1024, 2) . ' MB',
            'memory_peak' => round(memory_get_peak_usage(true) / 1024 / 1024, 2) . ' MB',
            'uptime' => $this->getUptime(),
        ];

        $httpCode = $status['status'] === 'healthy' ? 200 : 503;
        return response()->json($status, $httpCode);
    }

    /**
     * Simplified liveness probe (for Kubernetes/Docker).
     * Returns 200 if application is running.
     *
     * @OA\Get(
     *   path="/health/liveness",
     *   tags={"Health"},
     *   summary="Liveness probe",
     *   description="Returns 200 when the application process is alive.",
     *   @OA\Response(
     *     response=200,
     *     description="Service is alive",
     *     @OA\JsonContent(
     *       type="object",
     *       @OA\Property(property="status", type="string", example="alive")
     *     )
     *   )
     * )
     */
    public function liveness(): JsonResponse
    {
        return response()->json(['status' => 'alive'], 200);
    }

    /**
     * Readiness probe (for Kubernetes/Docker).
     * Returns 200 if application is ready to serve traffic.
        *
        * @OA\Get(
        *   path="/health/readiness",
        *   tags={"Health"},
        *   summary="Readiness probe",
        *   description="Checks critical dependencies and returns 200 when the service is ready to receive traffic.",
        *   @OA\Response(
        *     response=200,
        *     description="Service is ready",
        *     @OA\JsonContent(
        *       type="object",
        *       @OA\Property(property="status", type="string", example="ready")
        *     )
        *   ),
        *   @OA\Response(
        *     response=503,
        *     description="Service not ready",
        *     @OA\JsonContent(
        *       type="object",
        *       @OA\Property(property="status", type="string", example="not_ready"),
        *       @OA\Property(property="error", type="string")
        *     )
        *   )
        * )
     */
    public function readiness(): JsonResponse
    {
        try {
            // Check critical dependencies only
            DB::connection()->getPdo();
            Redis::ping();
            
            return response()->json(['status' => 'ready'], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'not_ready',
                'error' => $e->getMessage(),
            ], 503);
        }
    }

    /**
     * Get application uptime (approximate).
     */
    private function getUptime(): string
    {
        $uptimeFile = storage_path('framework/down');
        if (file_exists($uptimeFile)) {
            return 'maintenance mode';
        }

        // Approximate uptime based on cache
        $bootTime = Cache::remember('app_boot_time', 86400, fn() => now());
        $uptime = now()->diffForHumans($bootTime, true);
        
        return $uptime;
    }
}
