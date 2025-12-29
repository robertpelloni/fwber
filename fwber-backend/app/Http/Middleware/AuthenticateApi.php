<?php

namespace App\Http\Middleware;

use App\Facades\SecurityLog;
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
        // If a user is already authenticated AND no Authorization header is present, bypass token check.
        if (auth()->check() && !str_starts_with($header, 'Bearer ')) {
            return $next($request);
        }

        if (! str_starts_with($header, 'Bearer ')) {
            SecurityLog::authFailed([
                'reason' => 'Missing Bearer header',
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent()
            ]);
            return $this->unauthorized();
        }

        $plainToken = trim(substr($header, 7));

        if ($plainToken === '') {
            SecurityLog::authFailed([
                'reason' => 'Empty token',
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent()
            ]);
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
                SecurityLog::authSuccess([
                    'type' => 'dev_bypass',
                    'user_id' => $user->id,
                    'ip' => $request->ip()
                ]);
                return $next($request);
            }
        }

        $hashed = hash('sha256', $plainToken);
        $apiToken = ApiToken::query()->with('user')->where('token', $hashed)->first();

        if (! $apiToken || ! $apiToken->user) {
            SecurityLog::authFailed([
                'reason' => 'Invalid token',
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'token_prefix' => substr($plainToken, 0, 8) . '...'
            ]);
            return $this->unauthorized();
        }

        // Check for token expiration (Default: 30 days)
        // TODO: Move expiration time to config
        $expirationDays = 30;
        if ($apiToken->created_at->addDays($expirationDays)->isPast()) {
            SecurityLog::tokenExpired([
                'user_id' => $apiToken->user->id,
                'ip' => $request->ip(),
                'token_created_at' => $apiToken->created_at->toIso8601String()
            ]);
            return response()->json(['message' => 'Token expired. Please login again.'], 401);
        }

        $apiToken->forceFill(['last_used_at' => now()])->save();

        auth()->setUser($apiToken->user);

        SecurityLog::authSuccess([
            'user_id' => $apiToken->user->id,
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent()
        ]);

        return $next($request);
    }

    private function unauthorized(): Response
    {
        return response()->json([
            'message' => 'Unauthenticated.',
        ], Response::HTTP_UNAUTHORIZED);
    }
}
