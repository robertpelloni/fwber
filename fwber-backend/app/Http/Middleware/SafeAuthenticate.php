<?php

namespace App\Http\Middleware;

use Illuminate\Auth\Middleware\Authenticate as Middleware;
use Illuminate\Support\Facades\Log;
use Closure;

class SafeAuthenticate extends Middleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  string[]  ...$guards
     * @return mixed
     *
     * @throws \Illuminate\Auth\AuthenticationException
     */
    public function handle($request, Closure $next, ...$guards)
    {
        try {
            Log::info('SafeAuthenticate: Starting authentication check for guards: ' . implode(', ', $guards));
            
            $response = parent::handle($request, $next, ...$guards);
            
            Log::info('SafeAuthenticate: Authentication successful.');
            
            return $response;
        } catch (\Illuminate\Auth\AuthenticationException $e) {
            // This is a normal "not logged in" exception, let it bubble up
            throw $e;
        } catch (\Throwable $e) {
            Log::error('SafeAuthenticate: CRASH DETECTED!', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'error' => 'Authentication Middleware Crash',
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ], 500);
        }
    }
}
