<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

use Illuminate\Support\Facades\Log;

/**
 * Injects structured logging context into each request.
 * 
 * Adds X-Request-ID header to responses for distributed tracing.
 */
class InjectLoggingContext
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        try {
            // Generate or retrieve request ID
            $requestId = $request->header('X-Request-ID') ?? Str::uuid()->toString();
            $request->attributes->set('request_id', $requestId);

            // Context to be added to all logs
            $context = [
                'request_id' => $requestId,
                'ip' => $request->ip(),
                'user_id' => null, // Default to null
                'url' => $request->fullUrl(),
                'method' => $request->method(),
            ];

            // Safely attempt to get user ID
            try {
                if ($user = $request->user()) {
                    $context['user_id'] = $user->id;
                }
            } catch (\Throwable $e) {
                // Ignore auth errors during logging context setup
            }

            // Pass correlation ID through if present (for microservice tracing)
            if ($correlationId = $request->header('X-Correlation-ID')) {
                $request->attributes->set('correlation_id', $correlationId);
                $context['correlation_id'] = $correlationId;
            }

            // Register context with Laravel's logger
            Log::withContext($context);
        } catch (\Throwable $e) {
            // Fail silently if logging setup fails
        }

        $response = $next($request);

        try {
            // Inject request ID into response headers for client-side debugging
            if ($response instanceof Response) {
                $response->headers->set('X-Request-ID', $requestId ?? '');
            }
        } catch (\Throwable $e) {
            // Fail silently
        }

        return $response;
    }
}
