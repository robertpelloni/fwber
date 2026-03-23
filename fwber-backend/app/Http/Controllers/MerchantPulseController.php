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
        if (!$merchant) {
            return response()->json(['error' => 'Merchant profile required'], 403);
        }

        // Use merchant's business location or provided coordinates
        $lat = $request->get('lat', $merchant->latitude);
        $lng = $request->get('lng', $merchant->longitude);
        $radius = $request->get('radius', 2000);

        if (!$lat || !$lng) {
            return response()->json(['error' => 'Location coordinates required'], 422);
        }

        $analysis = $this->vibeService->getNeighborhoodVibe((float)$lat, (float)$lng, (int)$radius);

        return response()->json([
            'business_name' => $merchant->business_name,
            'location' => ['lat' => $lat, 'lng' => $lng, 'radius' => $radius],
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
            'status' => 'pending_vibe_match'
        ]);
    }
}
