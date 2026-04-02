<?php

namespace App\Http\Controllers;

use App\Services\LocalPulseAnalyticsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MerchantPulseController extends Controller
{
    public function __construct(
        private readonly LocalPulseAnalyticsService $vibeService
    ) {}

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
            'discount_code' => 'nullable|string',
            'vibe_target' => 'nullable|string', // e.g. "Only broadcast if vibe is 'Energetic'"
        ]);

        $merchant = Auth::user()->merchantProfile;

        // Logic to create a 'promotion' type ProximityArtifact
        // In a real implementation, this would also check the current vibe
        // against the 'vibe_target' before fulfilling the broadcast.

        return response()->json([
            'message' => 'Vibe-targeted promotion queued for broadcast.',
            'status' => 'pending_vibe_match',
        ]);
    }
}
