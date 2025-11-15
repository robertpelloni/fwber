<?php

use App\Http\Middleware\AuthenticateApi;
use App\Http\Middleware\SecurityHeaders;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        apiPrefix: 'api'
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Add security headers to all requests
        $middleware->append(SecurityHeaders::class);

        $middleware->alias([
            'auth.api' => AuthenticateApi::class,
            'profile.complete' => \App\Http\Middleware\EnsureProfileComplete::class,
            'presence.update' => \App\Http\Middleware\UpdateLastSeen::class,
            'auth.moderator' => \App\Http\Middleware\EnsureModerator::class,
            'feature' => \App\Http\Middleware\FeatureEnabled::class,
        ]);

        // Enable CORS for API routes
        $middleware->api(prepend: [
            \App\Http\Middleware\CorsMiddleware::class,
            \App\Http\Middleware\UpdateLastSeen::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
