<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
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
        if (!config('apm.enabled', false)) {
            return $next($request);
        }

        $startTime = microtime(true);
        
        // Enable query logging if not already enabled
        // Note: In production with high traffic, this might add overhead.
        // Ideally, we only enable this if we suspect slowness or sampling.
        // For now, we'll enable it to capture the count.
        DB::enableQueryLog();
        DB::flushQueryLog();

        $response = $next($request);

        $duration = (microtime(true) - $startTime) * 1000; // in milliseconds
        $queryCount = count(DB::getQueryLog());
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
