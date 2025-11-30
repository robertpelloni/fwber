<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class SentryContext
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (app()->bound('sentry')) {
            \Sentry\configureScope(function (\Sentry\State\Scope $scope) use ($request): void {
                // Add user context if authenticated
                if ($request->user()) {
                    $scope->setUser([
                        'id' => $request->user()->id,
                        'email' => $request->user()->email,
                    ]);
                }

                // Add request context
                $scope->setTag('environment', config('app.env'));
                $scope->setTag('http_method', $request->method());
                $scope->setTag('route', $request->route()?->getName() ?? 'unknown');
                
                // Add request metadata
                $scope->setContext('request', [
                    'url' => $request->fullUrl(),
                    'method' => $request->method(),
                    'ip' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                ]);
            });
        }

        return $next($request);
    }
}
