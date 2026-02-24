<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Symfony\Component\HttpFoundation\Response;
use App\Models\SlowRequest;

class ApmMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Basic Request Counting (Always enabled for Analytics)
        try {
            $today = now()->format('Y-m-d');
            Redis::incr("apm:requests:{$today}");
            Redis::expire("apm:requests:{$today}", 172800); // 48h TTL
        } catch (\Throwable $e) {
            // Fail silently if Redis is down or extension is missing
        }

        if (!config('apm.enabled', false)) {
            return $next($request);
        }

        $startTime = microtime(true);
        
        $queryCount = 0;
        $slowQueries = [];
        
        // Use DB::listen instead of enableQueryLog to save memory
        // This is safer for production as it doesn't store the full query log in memory
        DB::listen(function ($query) use (&$queryCount, &$slowQueries) {
            $queryCount++;
            // Capture queries taking longer than 50ms
            if ($query->time > 50) {
                // Limit the number of slow queries captured to prevent huge payloads
                if (count($slowQueries) < 20) {
                    $slowQueries[] = [
                        'sql' => $query->sql,
                        'time' => $query->time,
                        'connection' => $query->connectionName,
                    ];
                }
            }
        });

        $response = $next($request);

        // Track Errors (Always enabled for Analytics)
        if ($response->getStatusCode() >= 500) {
            try {
                $today = now()->format('Y-m-d');
                Redis::incr("apm:errors:{$today}");
                Redis::expire("apm:errors:{$today}", 172800);
            } catch (\Throwable $e) {
                // Fail silently
            }
        }

        $duration = (microtime(true) - $startTime) * 1000; // in milliseconds
        $memoryUsage = memory_get_peak_usage(true) / 1024; // KB

        // Log slow requests
        if ($duration > config('apm.slow_request_threshold', 1000)) {
            try {
                SlowRequest::create([
                    'user_id' => $request->user()?->id,
                    'method' => $request->method(),
                    'url' => $request->fullUrl(),
                    'route_name' => $request->route()?->getName(),
                    'action' => $request->route()?->getActionName(),
                    'duration_ms' => $duration,
                    'db_query_count' => $queryCount,
                    'memory_usage_kb' => $memoryUsage,
                    'slowest_queries' => !empty($slowQueries) ? $slowQueries : null,
                    'ip' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                    'payload' => $request->isMethod('GET') ? null : $request->except(['password', 'password_confirmation']),
                ]);
            } catch (\Exception $e) {
                Log::error('Failed to log slow request: ' . $e->getMessage());
            }

            Log::warning('Slow Request Detected', [
                'url' => $request->fullUrl(),
                'method' => $request->method(),
                'duration_ms' => round($duration, 2),
                'queries' => $queryCount,
                'memory_kb' => round($memoryUsage, 2),
                'ip' => $request->ip(),
                'user_id' => $request->user()?->id,
            ]);
        }

        // Here you would typically send metrics to Datadog/NewRelic
        // e.g. Datadog::timing('request.duration', $duration, ['path' => $request->path()]);

        return $response;
    }
}
