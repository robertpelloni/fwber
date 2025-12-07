<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class FeatureEnabled
{
    /**
     * Handle an incoming request.
     *
     * Usage in routes: ->middleware('feature:recommendations')
     */
    public function handle(Request $request, Closure $next, string $feature)
    {
        $enabled = (bool) config('features.' . $feature, false);
        if (!$enabled) {
            // Return 404 to avoid feature discovery when disabled
            return response()->json(['error' => 'Not Found'], 404);
        }
        return $next($request);
    }
}
