<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use App\Models\DailyActiveUser;
use App\Services\StreakService;
use Symfony\Component\HttpFoundation\Response;

class TrackUserActivity
{
    protected StreakService $streakService;

    public function __construct(StreakService $streakService)
    {
        $this->streakService = $streakService;
    }

    public function handle(Request $request, Closure $next): Response
    {
        try {
            if (Auth::check()) {
                $user = Auth::user();
                $today = now()->toDateString();
                $cacheKey = "user_active:{$user->id}:{$today}";

                if (!Cache::has($cacheKey)) {
                    // Record DAU
                    try {
                        DailyActiveUser::firstOrCreate([
                            'user_id' => $user->id,
                            'date' => $today,
                        ]);
                    } catch (\Exception $e) {
                        // Ignore if table missing
                    }

                    // Update Streak and Last Active
                    try {
                        $this->streakService->updateStreak($user);
                    } catch (\Exception $e) {
                        // Ignore if streak service fails
                    }

                    // Cache for 24 hours
                    Cache::put($cacheKey, true, now()->addDay());
                }
            }
        } catch (\Throwable $e) {
            // Fail silently to prevent blocking the request
            \Illuminate\Support\Facades\Log::error('TrackUserActivity error: ' . $e->getMessage());
        }

        return $next($request);
    }
}
