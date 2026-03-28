<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->validateCsrfTokens(except: [
            'api/*',
        ]);
        $middleware->append(\App\Http\Middleware\SecurityHeaders::class);
        $middleware->append(\App\Http\Middleware\ApmMiddleware::class);
        // $middleware->append(\App\Http\Middleware\InjectLoggingContext::class);

        $middleware->api(append: [
            \App\Http\Middleware\TrackUserActivity::class,
            \App\Http\Middleware\CheckDailyBonus::class,
        ]);

        $middleware->alias([
            'role' => \App\Http\Middleware\CheckUserRole::class,
            'feature' => \App\Http\Middleware\FeatureEnabled::class,
            'rate_limit_advanced' => \App\Http\Middleware\AdvancedRateLimiting::class,
            'edge.cache' => \App\Http\Middleware\EdgeCacheResponse::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        \Sentry\Laravel\Integration::handles($exceptions);

        $exceptions->render(function (Throwable $e, \Illuminate\Http\Request $request) {
            if ($e instanceof \Illuminate\Auth\AuthenticationException) {
                return response()->json(['message' => 'Unauthenticated.'], 401);
            }
            if ($e instanceof \Illuminate\Validation\ValidationException) {
                return response()->json([
                    'message' => $e->getMessage(),
                    'errors' => $e->errors(),
                ], 422);
            }
            if ($e instanceof \Symfony\Component\HttpKernel\Exception\HttpExceptionInterface) {
                return response()->json(['message' => $e->getMessage()], $e->getStatusCode());
            }

            if ($request->is('api/*')) {
                \Illuminate\Support\Facades\Log::error('API Exception: '.$e->getMessage(), [
                    'url' => $request->fullUrl(),
                    'trace' => $e->getTraceAsString(),
                ]);

                // Never expose stack traces in production
                if (! config('app.debug') || app()->environment('production')) {
                    return response()->json([
                        'message' => 'An error occurred processing your request.',
                        'error_id' => uniqid('err_'),
                        'debug_message' => app()->environment('production') ? 'Hidden in production' : $e->getMessage(),
                    ], 500);
                }

                // Allow default Laravel renderer in development
            }
        });
    })->create();
