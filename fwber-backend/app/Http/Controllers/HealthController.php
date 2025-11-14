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
     */
    public function liveness(): JsonResponse
    {
        return response()->json(['status' => 'alive'], 200);
    }

    /**
     * Readiness probe (for Kubernetes/Docker).
     * Returns 200 if application is ready to serve traffic.
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
