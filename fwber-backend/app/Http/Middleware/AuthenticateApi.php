<?php

namespace App\Http\Middleware;

use App\Models\ApiToken;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateApi
{
    public function handle(Request $request, Closure $next): Response
    {
        $header = (string) $request->header('Authorization');

        if (! str_starts_with($header, 'Bearer ')) {
            return $this->unauthorized();
        }

        $plainToken = trim(substr($header, 7));

        if ($plainToken === '') {
            return $this->unauthorized();
        }

        $hashed = hash('sha256', $plainToken);
        $apiToken = ApiToken::query()->with('user')->where('token', $hashed)->first();

        if (! $apiToken || ! $apiToken->user) {
            return $this->unauthorized();
        }

        $apiToken->forceFill(['last_used_at' => now()])->save();

        auth()->setUser($apiToken->user);

        return $next($request);
    }

    private function unauthorized(): Response
    {
        return response()->json([
            'message' => 'Unauthenticated.',
        ], Response::HTTP_UNAUTHORIZED);
    }
}
