<?php

namespace App\Http\Middleware;

use App\Models\DailyActiveUser;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

class TrackUserActivity
{
    public function handle(Request $request, Closure $next): Response
    {
        try {
            if (Auth::check()) {
                $user = Auth::user();
                $today = now()->toDateString();
                $cacheKey = "user_active:{$user->id}:{$today}";

                if (! Cache::has($cacheKey)) {
                    // Record DAU
                    try {
                        DailyActiveUser::firstOrCreate([
                            'user_id' => $user->id,
                            'date' => $today,
                        ]);
                    } catch (\Exception $e) {}

                    // Update Last Active
                    $user->update(['last_active_at' => now()]);

                    Cache::put($cacheKey, true, now()->addDay());
                }
            }
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('TrackUserActivity error: '.$e->getMessage());
        }

        return $next($request);
    }
}
