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
        try {
            if ($request->user()) {
                $user = $request->user();
                $now = now();

                // Check if bonus already claimed today
                // Use try-catch for property access in case column is missing from model definition but not DB, or vice versa
                try {
                    $lastBonus = $user->last_daily_bonus_at;
                    if (is_string($lastBonus)) {
                        $lastBonus = \Illuminate\Support\Carbon::parse($lastBonus);
                    }
                } catch (\Exception $e) {
                    $lastBonus = null;
                }

                if (!$lastBonus || $lastBonus->lt($now->startOfDay())) {

                    // Award 10 Tokens
                    try {
                        $service = app(TokenDistributionService::class);
                        $service->awardTokens($user, 10, 'daily_login', 'Daily Login Bonus');

                        $user->last_daily_bonus_at = $now;
                        $user->save();
                    } catch (\Exception $e) {
                        // Ignore if token service fails or save fails (missing column)
                    }
                }
            }
        } catch (\Exception $e) {
            // Fail silently
        }

        return $next($request);
    }
}
