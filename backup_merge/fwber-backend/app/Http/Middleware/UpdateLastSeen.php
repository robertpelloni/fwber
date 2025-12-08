<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class UpdateLastSeen
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        // Update presence for authenticated users with a small debounce to avoid excessive writes
        if (Auth::check()) {
            $user = Auth::user();

            $shouldUpdate = !$user->last_seen_at
                || now()->diffInSeconds($user->last_seen_at) >= 60;

            if ($shouldUpdate) {
                // Avoid triggering events/listeners in tests
                $user->forceFill(['last_seen_at' => now()])->saveQuietly();
            }
        }

        return $response;
    }
}
