<?php

namespace App\Http\Controllers;

use App\Services\MercurePublisher;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use OpenApi\Attributes as OA;

class MercureAuthController extends Controller
{
    protected MercurePublisher $mercurePublisher;

    public function __construct(MercurePublisher $mercurePublisher)
    {
        $this->mercurePublisher = $mercurePublisher;
    }

    /**
     * Set Mercure authorization cookie for authenticated user
     * 
     * @OA\Get(
     *     path="/mercure/cookie",
     *     tags={"Mercure"},
     *     summary="Get Mercure authorization cookie",
     *     description="Set an authorization cookie for subscribing to Mercure SSE topics",
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(response=200, description="Authorization cookie set",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string"),
     *             @OA\Property(property="topics", type="array", @OA\Items(type="string")),
     *             @OA\Property(property="expires_in", type="integer", example=3600)
     *         )
     *     ),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized")
     * )
     */
    public function cookie(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            
            if (!$user) {
                return response()->json(['error' => 'Unauthorized'], 401);
            }

            // Define topics the user can subscribe to
            // We authorize both absolute and relative paths to ensure compatibility
            $topics = [
                "https://fwber.me/users/{$user->id}",
                "https://fwber.me/users/{$user->id}/*",
                "https://fwber.me/bulletin-boards/*",
                "https://fwber.me/public/*",
                "/users/{$user->id}",
                "/users/{$user->id}/*",
                "/bulletin-boards/*",
                "/public/*"
            ];

            // Generate subscriber JWT
            $jwt = $this->mercurePublisher->generateSubscriberJWT($topics, $user->id, 60);

            // Get domain configuration
            $domain = config('services.mercure.cookie_domain');
            $publicUrl = config('services.mercure.public_url');
            $host = parse_url($publicUrl, PHP_URL_HOST) ?: $domain;

            // Set the authorization cookie
            $response = response()->json([
                'message' => 'Mercure authorization cookie set',
                'topics' => $topics,
                'expires_in' => 3600, // 1 hour
                'token' => $jwt, // Return token for manual handling if cookies fail
            ]);

            $response->cookie(
                'mercureAuthorization',
                $jwt,
                60, // 1 hour
                '/.well-known/mercure',
                $domain ?: $host,
                true, // secure
                true, // httpOnly
                false, // raw
                'None' // sameSite
            );

            Log::info('Mercure authorization cookie set', [
                'user_id' => $user->id,
                'topics' => $topics
            ]);

            return $response;

        } catch (\Throwable $e) {
            Log::error('Failed to set Mercure authorization cookie', [
                'user_id' => $user->id ?? null,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'error' => 'Failed to set authorization cookie'
            ], 500);
        }
    }

    /**
     * Get current Mercure authorization status
     * 
     * @OA\Get(
     *     path="/mercure/status",
     *     tags={"Mercure"},
     *     summary="Get Mercure authorization status",
     *     description="Check current Mercure authorization status and available topics",
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(response=200, description="Authorization status",
     *         @OA\JsonContent(
     *             @OA\Property(property="authorized", type="boolean", example=true),
     *             @OA\Property(property="user_id", type="integer"),
     *             @OA\Property(property="topics", type="array", @OA\Items(type="string")),
     *             @OA\Property(property="mercure_url", type="string")
     *         )
     *     ),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized")
     * )
     */
    public function status(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            
            if (!$user) {
                return response()->json(['error' => 'Unauthorized'], 401);
            }

            $topics = [
                "https://fwber.me/users/{$user->id}/*",
                "https://fwber.me/bulletin-boards/*",
                "https://fwber.me/public/*"
            ];

            return response()->json([
                'authorized' => true,
                'user_id' => $user->id,
                'topics' => $topics,
                'mercure_url' => config('services.mercure.public_url')
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to get Mercure authorization status', [
                'user_id' => $user->id ?? null,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'error' => 'Failed to get authorization status'
            ], 500);
        }
    }
}
