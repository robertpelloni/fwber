<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EdgeCacheResponse
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @param  int  $sMaxAge
     * @param  int  $maxAge
     */
    public function handle(Request $request, Closure $next, $sMaxAge = 60, $maxAge = 30): Response
    {
        $response = $next($request);

        // Only cache successful GET/HEAD requests
        if ($request->isMethodSafe() && $response->isSuccessful()) {
            $response->header('Cache-Control', "public, max-age={$maxAge}, s-maxage={$sMaxAge}, stale-while-revalidate=30");
        }

        return $response;
    }
}
