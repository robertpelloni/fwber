<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreVenueCheckinRequest;
use App\Models\Venue;
use App\Models\VenueCheckin;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class VenueCheckinController extends Controller
{
    /**
     * Check in to a venue.
     */
    public function store(StoreVenueCheckinRequest $request, $venueId)
    {
        $venue = Venue::findOrFail($venueId);
        
        // Verify distance (must be within 500m)
        $distance = $this->calculateDistance(
            $request->latitude,
            $request->longitude,
            $venue->latitude,
            $venue->longitude
        );

        if ($distance > 0.5) { // 0.5 km = 500m
            return response()->json([
                'message' => 'You are too far from the venue to check in.',
                'distance_km' => round($distance, 3)
            ], 403);
        }

        $user = Auth::user();

        // Auto-checkout from other venues
        VenueCheckin::where('user_id', $user->id)
            ->whereNull('checked_out_at')
            ->update(['checked_out_at' => now()]);

        $checkin = VenueCheckin::create([
            'user_id' => $user->id,
            'venue_id' => $venue->id,
            'message' => $request->message,
        ]);

        return response()->json($checkin, 201);
    }

    /**
     * Calculate distance between two points using Haversine formula.
     * Returns distance in kilometers.
     */
    private function calculateDistance($lat1, $lon1, $lat2, $lon2)
    {
        $earthRadius = 6371; // Radius of the earth in km

        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);

        $a = sin($dLat / 2) * sin($dLat / 2) +
             cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
             sin($dLon / 2) * sin($dLon / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c;
    }

    /**
     * Get active check-ins for a venue.
     */
    public function index($venueId)
    {
        $checkins = VenueCheckin::with('user')
            ->where('venue_id', $venueId)
            ->whereNull('checked_out_at')
            ->latest()
            ->paginate(20);

        return response()->json($checkins);
    }

    /**
     * Check out from a venue.
     */
    public function destroy($venueId)
    {
        $user = Auth::user();
        
        VenueCheckin::where('user_id', $user->id)
            ->where('venue_id', $venueId)
            ->whereNull('checked_out_at')
            ->update(['checked_out_at' => now()]);

        return response()->json(['message' => 'Checked out successfully']);
    }
    
    /**
     * Get current check-in status for the user.
     */
    public function current()
    {
        $checkin = VenueCheckin::with('venue')
            ->where('user_id', Auth::id())
            ->whereNull('checked_out_at')
            ->latest()
            ->first();
            
        return response()->json($checkin);
    }
}
