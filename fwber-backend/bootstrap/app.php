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
        $middleware->append(\App\Http\Middleware\SecurityHeaders::class);
        $middleware->append(\App\Http\Middleware\ApmMiddleware::class);
        // $middleware->append(\App\Http\Middleware\InjectLoggingContext::class);
        
        $middleware->api(append: [
            \App\Http\Middleware\TrackUserActivity::class,
            \App\Http\Middleware\CheckDailyBonus::class,
        ]);

        $middleware->alias([
            'feature' => \App\Http\Middleware\FeatureEnabled::class,
            'rate_limit_advanced' => \App\Http\Middleware\AdvancedRateLimiting::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        \Sentry\Laravel\Integration::handles($exceptions);

        $exceptions->render(function (Throwable $e, Request $request) {
            if ($request->is('api/*')) {
                // Never expose stack traces in production
                if (!config('app.debug') || app()->environment('production')) {
                    return response()->json([
                        'message' => 'An error occurred processing your request.',
                        'error_id' => uniqid('err_'),
                    ], 500);
                }
                
                // Allow default Laravel renderer in development
            }
        });
    })->create();
