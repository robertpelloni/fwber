<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;

/**
 * @OA\Tag(
 *     name="Configuration",
 *     description="System configuration and feature flags management"
 * )
 */
class ConfigController extends Controller
{
    /**
     * Get current feature flag states.
     *
     * @OA\Get(
     *     path="/api/config/features",
     *     summary="Get feature flags",
     *     description="Returns current state of all feature flags. Requires moderator access.",
     *     operationId="getFeatureFlags",
     *     tags={"Configuration"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Feature flags retrieved successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="features", type="object"),
     *             @OA\Property(property="source", type="string", example="config"),
     *             @OA\Property(property="timestamp", type="string", format="date-time")
     *         )
     *     ),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=403, ref="#/components/responses/Forbidden")
     * )
     */
    public function getFeatures(): JsonResponse
    {
        $features = config('features', []);

        return response()->json([
            'features' => $features,
            'source' => 'config',
            'timestamp' => now()->toISOString(),
        ]);
    }

    /**
     * Update feature flag states (runtime override).
     *
     * Note: This creates runtime overrides stored in cache. For permanent changes,
     * update the .env file and clear config cache.
     *
     * @OA\Put(
     *     path="/api/config/features",
     *     summary="Update feature flags",
     *     description="Updates feature flag states at runtime. Changes are cached and will reset on cache clear. Requires moderator access.",
     *     operationId="updateFeatureFlags",
     *     tags={"Configuration"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(
     *                 property="features",
     *                 type="object",
     *                 description="Feature flags to update",
     *                 example={"chatrooms": true, "recommendations": false}
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Feature flags updated successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Feature flags updated"),
     *             @OA\Property(property="features", type="object"),
     *             @OA\Property(property="updated", type="array", @OA\Items(type="string")),
     *             @OA\Property(property="timestamp", type="string", format="date-time")
     *         )
     *     ),
     *     @OA\Response(response=400, ref="#/components/responses/BadRequest"),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=403, ref="#/components/responses/Forbidden")
     * )
     */
    public function updateFeatures(Request $request): JsonResponse
    {
        $request->validate([
            'features' => 'required|array',
            'features.*' => 'boolean',
        ]);

        $validFlags = array_keys(config('features', []));
        $requestedFlags = $request->input('features', []);
        $updated = [];

        foreach ($requestedFlags as $flag => $value) {
            if (!in_array($flag, $validFlags)) {
                continue;
            }

            // Store runtime override in cache
            $cacheKey = "feature_override:{$flag}";
            Cache::put($cacheKey, $value, now()->addDays(7));

            // Update config for current request
            Config::set("features.{$flag}", $value);

            $updated[] = $flag;
        }

        // Log the change for audit
        \Log::info('Feature flags updated', [
            'user_id' => Auth::id(),
            'updated_flags' => $updated,
            'new_values' => array_intersect_key($requestedFlags, array_flip($updated)),
        ]);

        return response()->json([
            'message' => 'Feature flags updated',
            'features' => config('features'),
            'updated' => $updated,
            'timestamp' => now()->toISOString(),
        ]);
    }

    /**
     * Get Mercure/WebSocket connection health status.
     *
     * @OA\Get(
     *     path="/api/config/health",
     *     summary="Get system health",
     *     description="Returns health status of various system components including Mercure.",
     *     operationId="getSystemHealth",
     *     tags={"Configuration"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Health status retrieved",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="mercure", type="object",
     *                 @OA\Property(property="configured", type="boolean"),
     *                 @OA\Property(property="public_url", type="string"),
     *                 @OA\Property(property="status", type="string", example="healthy")
     *             ),
     *             @OA\Property(property="cache", type="object",
     *                 @OA\Property(property="driver", type="string"),
     *                 @OA\Property(property="status", type="string")
     *             ),
     *             @OA\Property(property="queue", type="object",
     *                 @OA\Property(property="driver", type="string"),
     *                 @OA\Property(property="status", type="string")
     *             ),
     *             @OA\Property(property="timestamp", type="string", format="date-time")
     *         )
     *     ),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized")
     * )
     */
    public function getHealth(): JsonResponse
    {
        $mercureUrl = config('mercure.public_url') ?? env('MERCURE_PUBLIC_URL');
        $mercureConfigured = !empty($mercureUrl);

        // Check cache connectivity
        $cacheStatus = 'unknown';
        try {
            Cache::put('health_check', true, 10);
            $cacheStatus = Cache::get('health_check') ? 'healthy' : 'degraded';
            Cache::forget('health_check');
        } catch (\Exception $e) {
            $cacheStatus = 'unhealthy';
        }

        // Check queue connectivity
        $queueStatus = 'unknown';
        $queueDriver = config('queue.default');

        return response()->json([
            'mercure' => [
                'configured' => $mercureConfigured,
                'public_url' => $mercureUrl ?: null,
                'status' => $mercureConfigured ? 'configured' : 'not_configured',
            ],
            'cache' => [
                'driver' => config('cache.default'),
                'status' => $cacheStatus,
            ],
            'queue' => [
                'driver' => $queueDriver,
                'status' => $queueStatus,
            ],
            'features_enabled' => array_filter(config('features', []), fn($v) => $v === true),
            'timestamp' => now()->toISOString(),
        ]);
    }
}
