<?php

namespace App\Http\Controllers;

use App\Events\ProximityArtifactEvent;
use App\Models\ProximityArtifact;
use App\Services\LocalPulseAnalyticsService;
use App\Services\TokenDistributionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class MerchantPulseController extends Controller
{
    public function __construct(
        private readonly LocalPulseAnalyticsService $vibeService,
        private readonly TokenDistributionService $tokenService
    ) {}

    private const BROADCAST_TOKEN_COST = 50;

    /**
     * Get the real-time "Vibe" of the area around the merchant's business.
     */
    public function getVibe(Request $request)
    {
        $merchant = Auth::user()->merchantProfile;
        if (! $merchant) {
            return response()->json(['error' => 'Merchant profile required'], 403);
        }

        $latestPromotion = $merchant->promotions()
            ->whereNotNull('lat')
            ->whereNotNull('lng')
            ->latest('updated_at')
            ->first();

        // Merchant profiles do not currently persist lat/lng, so promotions are the best owned location source.
        $lat = $request->get('lat', $latestPromotion?->lat);
        $lng = $request->get('lng', $latestPromotion?->lng);
        $radius = $request->get('radius', 2000);

        if (! $lat || ! $lng) {
            return response()->json([
                'error' => 'Location coordinates required',
                'message' => 'Create or update a promotion with a map location before requesting neighborhood vibe analysis.',
            ], 422);
        }

        $analysis = $this->vibeService->getNeighborhoodVibe((float) $lat, (float) $lng, (int) $radius);

        return response()->json([
            'business_name' => $merchant->business_name,
            'location' => ['lat' => $lat, 'lng' => $lng, 'radius' => $radius],
            'location_source' => $request->has('lat') && $request->has('lng') ? 'request' : 'latest_promotion',
            'analysis' => $analysis,
        ]);
    }

    /**
     * Trigger a "Vibe-Matched" broadcast.
     * Allows merchants to send a promotion specifically when the neighborhood sentiment is right.
     */
    public function broadcast(Request $request)
    {
        $request->validate([
            'content' => 'required|string|max:280',
            'discount_code' => 'nullable|string|max:50',
            'vibe_target' => 'nullable|string|in:any,energetic,chill,romantic',
        ]);

        $merchant = Auth::user()->merchantProfile;
        if (! $merchant) {
            return response()->json(['error' => 'Merchant profile required'], 403);
        }

        $latestPromotion = $merchant->promotions()
            ->whereNotNull('lat')
            ->whereNotNull('lng')
            ->latest('updated_at')
            ->first();

        if (! $latestPromotion) {
            return response()->json([
                'error' => 'Location coordinates required',
                'message' => 'Create or update a promotion with a map location before sending a vibe broadcast.',
            ], 422);
        }

        $vibeTarget = strtolower((string) $request->input('vibe_target', 'any'));
        $analysis = $this->vibeService->getNeighborhoodVibe(
            (float) $latestPromotion->lat,
            (float) $latestPromotion->lng,
            (int) $latestPromotion->radius
        );

        $currentVibe = strtolower((string) ($analysis['vibe'] ?? ''));
        $vibeMatched = $vibeTarget === 'any' || str_contains($currentVibe, $vibeTarget);

        if (! $vibeMatched) {
            return response()->json([
                'error' => 'Current neighborhood vibe does not match target',
                'message' => "The live neighborhood vibe is '{$analysis['vibe']}' right now, so this broadcast was not sent.",
                'status' => 'blocked_vibe_mismatch',
                'current_vibe' => $analysis['vibe'] ?? null,
                'vibe_target' => $vibeTarget,
                'token_cost' => self::BROADCAST_TOKEN_COST,
            ], 422);
        }

        $user = Auth::user();
        $artifact = DB::transaction(function () use ($request, $merchant, $latestPromotion, $user, $vibeTarget, $analysis) {
            $this->tokenService->spendTokens($user, self::BROADCAST_TOKEN_COST, "Merchant vibe broadcast for {$merchant->business_name}");

            return ProximityArtifact::create([
                'user_id' => $user->id,
                'type' => 'announce',
                'content' => trim((string) $request->input('content')),
                'location_lat' => $latestPromotion->lat,
                'location_lng' => $latestPromotion->lng,
                'visibility_radius_m' => (int) $latestPromotion->radius,
                'expires_at' => now()->addHours(2),
                'moderation_status' => 'clean',
                'meta' => array_filter([
                    'source' => 'merchant_pulse_broadcast',
                    'business_name' => $merchant->business_name,
                    'merchant_profile_id' => $merchant->id,
                    'promotion_id' => $latestPromotion->id,
                    'promo_code' => $request->input('discount_code') ?: null,
                    'vibe_target' => $vibeTarget,
                    'vibe_snapshot' => $analysis['vibe'] ?? null,
                    'activity_score' => $analysis['activity_score'] ?? null,
                    'is_sponsored' => true,
                ], static fn ($value) => $value !== null && $value !== ''),
            ]);
        });

        try {
            ProximityArtifactEvent::dispatch('artifact_created', [
                'artifact_id' => $artifact->id,
                'user_id' => $user->id,
            ]);
        } catch (\Throwable $e) {
            \Log::warning('Pusher publish failed for merchant broadcast', [
                'artifact_id' => $artifact->id,
                'error' => $e->getMessage(),
            ]);
        }

        return response()->json([
            'message' => 'Vibe-targeted promotion broadcast sent.',
            'status' => 'broadcast_sent',
            'artifact_id' => $artifact->id,
            'token_cost' => self::BROADCAST_TOKEN_COST,
            'remaining_balance' => $user->fresh()->token_balance,
            'current_vibe' => $analysis['vibe'] ?? null,
            'vibe_target' => $vibeTarget,
            'location_source' => 'latest_promotion',
        ]);
    }

    /**
     * @OA\Post(
     *   path="/merchant/pulse/{id}/deactivate",
     *   tags={"Merchant Pulse"},
     *   summary="Deactivate a broadcast",
     *   description="Deactivates an active live broadcast.",
     *   security={{"bearerAuth":{}}},
     *   @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *   @OA\Response(response=200, description="Deactivated"),
     *   @OA\Response(response=404, description="Broadcast not found")
     * )
     */
    public function deactivateBroadcast(Request $request, int $id)
    {
        $merchant = \App\Models\MerchantProfile::where('user_id', auth()->id())->firstOrFail();

        $artifact = \App\Models\ProximityArtifact::where('id', $id)
            ->where('creator_id', auth()->id())
            ->where('artifact_type', 'merchant_announce')
            ->firstOrFail();

        if ($artifact->expires_at && $artifact->expires_at->isPast()) {
            return response()->json(['message' => 'Broadcast is already expired'], 400);
        }

        $artifact->update([
            'expires_at' => now(),
            'is_anonymous' => true, // Hide it immediately from proximity feeds or mark as inactive
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Broadcast deactivated successfully.',
        ]);
    }
}
