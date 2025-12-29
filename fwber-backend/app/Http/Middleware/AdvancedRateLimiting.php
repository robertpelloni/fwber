<?php

namespace App\Http\Middleware;

use App\Facades\SecurityLog;
use Closure;
use Illuminate\Http\Request;
use App\Services\AdvancedRateLimitingService;
use Illuminate\Support\Facades\Log;

class AdvancedRateLimiting
{
    private AdvancedRateLimitingService $rateLimitingService;

    public function __construct(AdvancedRateLimitingService $rateLimitingService)
    {
        $this->rateLimitingService = $rateLimitingService;
    }

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next, string $action = 'api_call'): mixed
    {
        // In testing environment, bypass advanced rate limiting to avoid
        // external dependencies (e.g., Redis) and keep tests deterministic.
        if (app()->environment('testing')) {
            return $next($request);
        }

        $userId = $request->user()?->id ?? $request->ip();
        $context = $this->buildContext($request);

        // Check rate limit
        $rateLimitResult = $this->rateLimitingService->checkRateLimit($userId, $action, $context);

        if (!$rateLimitResult['allowed']) {
            // Check for suspicious activity
            $suspiciousActivity = $this->rateLimitingService->detectSuspiciousActivity($userId);
            
            if ($suspiciousActivity['suspicious']) {
                SecurityLog::suspiciousActivity([
                    'user_id' => $userId,
                    'action' => $action,
                    'reasons' => $suspiciousActivity['reasons'],
                    'ip' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                ]);
            } else {
                 SecurityLog::rateLimitExceeded([
                    'user_id' => $userId,
                    'action' => $action,
                    'limit' => $rateLimitResult['limit'] ?? 'unknown', 
                    'ip' => $request->ip(),
                ]);
            }

            return response()->json([
                'error' => 'Rate limit exceeded',
                'message' => $rateLimitResult['reason'],
                'retry_after' => $rateLimitResult['retry_after'],
                'remaining_tokens' => $rateLimitResult['remaining_tokens'],
                'reset_time' => $rateLimitResult['reset_time'],
            ], 429)->header('Retry-After', $rateLimitResult['retry_after']);
        }

        // Add rate limit headers to response
        $response = $next($request);
        
        $response->headers->set('X-RateLimit-Remaining', $rateLimitResult['remaining_tokens']);
        $response->headers->set('X-RateLimit-Reset', $rateLimitResult['reset_time']);
        $response->headers->set('X-RateLimit-Action', $action);

        return $response;
    }

    /**
     * Build context for rate limiting
     */
    private function buildContext(Request $request): array
    {
        $context = [
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'method' => $request->method(),
            'path' => $request->path(),
        ];

        // Add action-specific context
        if ($request->is('*/content-generation/*')) {
            $context['content'] = $request->input('content', '');
            $context['content_length'] = strlen($context['content']);
        }

        if ($request->is('*/photos/*') && $request->hasFile('photo')) {
            $file = $request->file('photo');
            $context['file_size'] = $file->getSize();
            $context['file_type'] = $file->getMimeType();
        }

        if ($request->is('*/bulletin-boards/*/messages')) {
            $context['message_length'] = strlen($request->input('content', ''));
            $context['board_id'] = $request->route('id');
        }

        if ($request->is('*/location/*')) {
            $context['latitude'] = $request->input('latitude');
            $context['longitude'] = $request->input('longitude');
            $context['accuracy'] = $request->input('accuracy');
        }

        return $context;
    }
}
