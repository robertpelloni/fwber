<?php

namespace App\Http\Controllers;

use App\Services\MercurePublisher;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class MercureAuthController extends Controller
{
    protected MercurePublisher $mercurePublisher;

    public function __construct(MercurePublisher $mercurePublisher)
    {
        $this->mercurePublisher = $mercurePublisher;
    }

    /**
     * Set Mercure authorization cookie for authenticated user
     */
    public function cookie(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            
            if (!$user) {
                return response()->json(['error' => 'Unauthorized'], 401);
            }

            // Define topics the user can subscribe to
            $topics = [
                "https://fwber.me/users/{$user->id}/*",
                "https://fwber.me/bulletin-boards/*",
                "https://fwber.me/public/*"
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
                'expires_in' => 3600 // 1 hour
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

        } catch (\Exception $e) {
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
