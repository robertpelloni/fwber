<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;

/**
 * Centralized health-status builder for both HTTP health endpoints and CLI
 * deployment verification.
 *
 * Keeping this logic in one place avoids drift between the API health surface
 * and the command-line checks operators will run during Hetzner provisioning.
 */
class HealthStatusService
{
    public function build(bool $includeMetrics = true): array
    {
        $status = [
            'status' => 'healthy',
            'timestamp' => now()->toIso8601String(),
            'version' => config('app.version', 'unknown'),
            'environment' => config('app.env'),
            'checks' => [],
        ];

        $status['checks']['database'] = $this->checkDatabase();
        $status['checks']['redis'] = $this->checkRedis();
        $status['checks']['cache'] = $this->checkCache();
        $status['checks']['storage'] = $this->checkStorage();
        $status['checks']['queue'] = $this->checkQueue();
        $status['checks']['broadcast'] = $this->checkBroadcast();

        foreach ($status['checks'] as $check) {
            if (($check['critical'] ?? false) && ($check['status'] ?? 'ok') === 'failed') {
                $status['status'] = 'unhealthy';
            }
        }

        if ($includeMetrics) {
            $status['metrics'] = [
                'memory_usage' => round(memory_get_usage(true) / 1024 / 1024, 2).' MB',
                'memory_peak' => round(memory_get_peak_usage(true) / 1024 / 1024, 2).' MB',
                'uptime' => $this->getUptime(),
            ];
        }

        return $status;
    }

    public function buildMetrics(): array
    {
        return [
            'redis' => $this->redisMetrics(),
            'database' => $this->databaseMetrics(),
        ];
    }

    protected function checkDatabase(): array
    {
        try {
            DB::connection()->getPdo();
            $driver = DB::connection()->getDriverName();
            $version = match ($driver) {
                'sqlite' => DB::select('SELECT sqlite_version() as version')[0]->version ?? 'unknown',
                'mysql', 'mariadb' => DB::select('SELECT VERSION() as version')[0]->version ?? 'unknown',
                'pgsql' => DB::select('SELECT version() as version')[0]->version ?? 'unknown',
                default => 'unknown',
            };

            return [
                'status' => 'ok',
                'critical' => true,
                'driver' => $driver,
                'version' => $version,
                'connection' => DB::connection()->getDatabaseName(),
            ];
        } catch (\Throwable $e) {
            return [
                'status' => 'failed',
                'critical' => true,
                'error' => $e->getMessage(),
            ];
        }
    }

    protected function checkRedis(): array
    {
        $redisRequired = $this->redisIsRequired();

        if (! class_exists(Redis::class) && ! function_exists('redis')) {
            return [
                'status' => $redisRequired ? 'failed' : 'skipped',
                'critical' => $redisRequired,
                'reason' => 'Redis facade or driver unavailable',
            ];
        }

        try {
            $pong = Redis::connection()->ping();

            return [
                'status' => 'ok',
                'critical' => $redisRequired,
                'ping' => is_scalar($pong) ? (string) $pong : 'PONG',
            ];
        } catch (\Throwable $e) {
            return [
                'status' => $redisRequired ? 'failed' : 'degraded',
                'critical' => $redisRequired,
                'error' => $e->getMessage(),
            ];
        }
    }

    protected function checkCache(): array
    {
        try {
            $testKey = 'health_check_'.time();
            Cache::put($testKey, 'test', 10);
            $retrieved = Cache::get($testKey);
            Cache::forget($testKey);

            return [
                'status' => $retrieved === 'test' ? 'ok' : 'failed',
                'critical' => true,
                'driver' => config('cache.default'),
            ];
        } catch (\Throwable $e) {
            return [
                'status' => 'failed',
                'critical' => true,
                'driver' => config('cache.default'),
                'error' => $e->getMessage(),
            ];
        }
    }

    protected function checkStorage(): array
    {
        $storagePath = storage_path('logs');
        $writable = is_writable($storagePath);

        return [
            'status' => $writable ? 'ok' : 'failed',
            'critical' => true,
            'path' => $storagePath,
            'writable' => $writable,
        ];
    }

    protected function checkQueue(): array
    {
        return [
            'status' => 'ok',
            'critical' => false,
            'connection' => config('queue.default'),
        ];
    }

    protected function checkBroadcast(): array
    {
        return [
            'status' => 'ok',
            'critical' => false,
            'connection' => config('broadcasting.default') ?? config('broadcast.default') ?? env('BROADCAST_CONNECTION', 'log'),
        ];
    }

    protected function redisMetrics(): array
    {
        try {
            $info = Redis::info();

            return [
                'used_memory_human' => $info['used_memory_human'] ?? null,
                'connected_clients' => $info['connected_clients'] ?? null,
                'instantaneous_ops_per_sec' => $info['instantaneous_ops_per_sec'] ?? null,
            ];
        } catch (\Throwable $e) {
            return ['error' => $e->getMessage()];
        }
    }

    protected function databaseMetrics(): array
    {
        try {
            $driver = DB::connection()->getDriverName();
            if ($driver === 'mysql') {
                $threads = DB::select('SHOW GLOBAL STATUS LIKE "Threads_connected"');

                return [
                    'threads_connected' => $threads[0]->Value ?? null,
                ];
            }

            return ['info' => 'Metrics only available for MySQL'];
        } catch (\Throwable $e) {
            return ['error' => $e->getMessage()];
        }
    }

    protected function getUptime(): string
    {
        $uptimeFile = storage_path('framework/down');
        if (file_exists($uptimeFile)) {
            return 'maintenance mode';
        }

        $bootTime = Cache::remember('app_boot_time', 86400, fn () => now());

        return now()->diffForHumans($bootTime, true);
    }

    protected function redisIsRequired(): bool
    {
        return in_array(config('cache.default'), ['redis'], true)
            || in_array(config('queue.default'), ['redis'], true)
            || in_array(config('session.driver'), ['redis'], true)
            || in_array(env('BROADCAST_CONNECTION', config('broadcasting.default', 'log')), ['reverb', 'redis'], true);
    }
}
