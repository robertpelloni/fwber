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
        if (Auth::check()) {
            $user = Auth::user();
            if (GlobalBan::where('bannable_identifier', (string) $user->id)->where('type', 'user')->exists()) {
                Auth::logout();
                return response()->json([
                    'error' => 'Your account has been globally banned by the community council.',
                ], 403);
            }
        }

        return $next($request);
    }
}
