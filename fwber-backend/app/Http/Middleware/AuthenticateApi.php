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

        // Support standard session-based auth for tests using actingAs() where no bearer token is supplied.
        // If a user is already authenticated (e.g. via actingAs in the test suite), bypass token check.
        if (auth()->check()) {
            return $next($request);
        }

        if (! str_starts_with($header, 'Bearer ')) {
            return $this->unauthorized();
        }

        $plainToken = trim(substr($header, 7));

        if ($plainToken === '') {
            return $this->unauthorized();
        }

        // Development bypass token: when running locally, allow a configured token
        // to authenticate as a development user without DB tokens.
        // This is safe only for local development and is disabled in non-local envs.
        if (app()->environment('local')) {
            $devBypass = (string) env('API_DEV_BYPASS_TOKEN', '');
            if ($devBypass !== '' && hash_equals($devBypass, $plainToken)) {
                // Find or create a development user
                $userModel = \App\Models\User::class;
                /** @var \Illuminate\Database\Eloquent\Model|\App\Models\User $user */
                $user = $userModel::query()->where('email', 'dev@fwber.me')->first();
                if (! $user) {
                    $user = $userModel::query()->create([
                        'name' => 'Dev User',
                        'email' => 'dev@fwber.me',
                        'password' => bcrypt(str()->random(32)),
                    ]);
                }

                auth()->setUser($user);
                return $next($request);
            }
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
