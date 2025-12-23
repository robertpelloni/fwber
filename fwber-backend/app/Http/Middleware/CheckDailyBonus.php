<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Services\TokenDistributionService;

class CheckDailyBonus
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user()) {
            $user = $request->user();
            $now = now();

            // Check if bonus already claimed today
            if (!$user->last_daily_bonus_at || $user->last_daily_bonus_at->lt($now->startOfDay())) {

                // Award 10 Tokens
                $service = app(TokenDistributionService::class);
                $service->awardTokens($user, 10, 'daily_login', 'Daily Login Bonus');

                $user->last_daily_bonus_at = $now;
                $user->save();
            }
        }

        return $next($request);
    }
}
