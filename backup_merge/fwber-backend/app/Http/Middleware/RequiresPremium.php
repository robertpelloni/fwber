<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Carbon\Carbon;

class RequiresPremium
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $isPremium = $user->tier === 'gold' && 
                     $user->tier_expires_at && 
                     Carbon::parse($user->tier_expires_at)->isFuture();

        if (!$isPremium) {
            return response()->json(['message' => 'Premium subscription required.'], 403);
        }

        return $next($request);
    }
}
