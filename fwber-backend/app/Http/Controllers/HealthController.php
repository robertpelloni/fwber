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
            error_log('Health Check: Testing Database...');
            DB::connection()->getPdo();
            
            $driver = DB::connection()->getDriverName();
            $dbVersion = 'unknown';
            
            if ($driver === 'sqlite') {
                $dbVersion = DB::select('SELECT sqlite_version() as version')[0]->version ?? 'unknown';
            } elseif ($driver === 'mysql' || $driver === 'mariadb') {
                $dbVersion = DB::select('SELECT VERSION() as version')[0]->version ?? 'unknown';
            } elseif ($driver === 'pgsql') {
                $dbVersion = DB::select('SELECT version() as version')[0]->version ?? 'unknown';
            }

            $status['checks']['database'] = [
                'status' => 'ok',
                'version' => $dbVersion,
                'connection' => DB::connection()->getDatabaseName(),
            ];
            error_log('Health Check: Database OK');
        } catch (\Throwable $e) {
            error_log('Health Check: Database FAILED - ' . $e->getMessage());
            $status['checks']['database'] = [
                'status' => 'failed',
                'error' => $e->getMessage(),
            ];
            $status['status'] = 'unhealthy';
        }

        // Redis connectivity
        try {
            error_log('Health Check: Testing Redis...');
            Redis::ping();
            $redisInfo = Redis::info('server');
            $status['checks']['redis'] = [
                'status' => 'ok',
                'version' => $redisInfo['redis_version'] ?? 'unknown',
            ];
            error_log('Health Check: Redis OK');
        } catch (\Throwable $e) {
            error_log('Health Check: Redis FAILED - ' . $e->getMessage());
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
        } catch (\Throwable $e) {
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

    /**
     * Expose infrastructure metrics for scaling decisions.
     *
     * @OA\Get(
     *   path="/health/metrics",
     *   tags={"Health"},
     *   summary="Infrastructure metrics",
     *   description="Returns Redis and Database load metrics.",
     *   @OA\Response(
     *     response=200,
     *     description="Metrics retrieved",
     *     @OA\JsonContent(
     *       type="object",
     *       @OA\Property(property="redis", type="object"),
     *       @OA\Property(property="database", type="object")
     *     )
     *   )
     * )
     */
    public function metrics(): JsonResponse
    {
        $metrics = [];

        // Redis Metrics
        try {
            $info = Redis::info();
            $metrics['redis'] = [
                'used_memory_human' => $info['used_memory_human'] ?? null,
                'connected_clients' => $info['connected_clients'] ?? null,
                'instantaneous_ops_per_sec' => $info['instantaneous_ops_per_sec'] ?? null,
            ];
        } catch (\Throwable $e) {
            $metrics['redis'] = ['error' => $e->getMessage()];
        }

        // Database Metrics (MySQL specific)
        try {
            $driver = DB::connection()->getDriverName();
            if ($driver === 'mysql') {
                $threads = DB::select('SHOW GLOBAL STATUS LIKE "Threads_connected"');
                $metrics['database'] = [
                    'threads_connected' => $threads[0]->Value ?? null,
                ];
            } else {
                $metrics['database'] = ['info' => 'Metrics only available for MySQL'];
            }
        } catch (\Throwable $e) {
            $metrics['database'] = ['error' => $e->getMessage()];
        }

        return response()->json($metrics);
    }
}
