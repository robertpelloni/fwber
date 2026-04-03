<?php

namespace App\Http\Middleware;

use App\Models\GlobalBan;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CheckGlobalBan
{
    public function handle(Request $request, Closure $next): Response
    {
        // Allow access to appeals and basic auth status even if banned
        if ($request->is('api/governance/appeals*') || $request->is('api/auth/me')) {
            return $next($request);
        }

        if (Auth::check()) {
            $user = Auth::user();
            if (GlobalBan::where('bannable_identifier', (string) $user->id)->where('type', 'user')->exists()) {
                // Return a specific error code that the frontend can use to redirect to /appeals
                return response()->json([
                    'error' => 'Your account has been globally banned by the community council.',
                    'code' => 'GLOBAL_BAN',
                    'can_appeal' => true
                ], 403);
            }
        }

        return $next($request);
    }
}
