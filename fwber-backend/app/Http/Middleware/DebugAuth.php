<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class DebugAuth
{
    public function handle(Request $request, Closure $next)
    {
        try {
            Log::info('DebugAuth: Attempting authentication...');
            
            if (Auth::guard('sanctum')->check()) {
                Log::info('DebugAuth: Check passed.');
                Auth::shouldUse('sanctum');
            } else {
                Log::info('DebugAuth: Check failed.');
                return response()->json(['error' => 'Unauthenticated (DebugAuth)'], 401);
            }
            
            Log::info('DebugAuth: Authentication successful. Proceeding to next middleware.');
            return $next($request);
            
        } catch (\Throwable $e) {
            Log::error('DebugAuth: Crash detected!', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'error' => 'Auth Middleware Crash',
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ], 500);
        }
    }
}
