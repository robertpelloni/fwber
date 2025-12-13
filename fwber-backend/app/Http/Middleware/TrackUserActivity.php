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
        if (Auth::check()) {
            $user = Auth::user();
            $today = now()->toDateString();
            $cacheKey = "user_active:{$user->id}:{$today}";

            if (!Cache::has($cacheKey)) {
                // Record DAU
                DailyActiveUser::firstOrCreate([
                    'user_id' => $user->id,
                    'date' => $today,
                ]);

                // Update Streak and Last Active
                $this->streakService->updateStreak($user);

                // Cache for 24 hours
                Cache::put($cacheKey, true, now()->addDay());
            }
        }

        return $next($request);
    }
}
