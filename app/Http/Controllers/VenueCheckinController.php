<?php

namespace App\Http\Controllers;

use App\Models\Venue;
use App\Models\VenueCheckin;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class VenueCheckinController extends Controller
{
    /**
     * Check in to a venue.
     */
    public function store(Request $request, $venueId)
    {
        $request->validate([
            'message' => 'nullable|string|max:255',
        ]);

        $venue = Venue::findOrFail($venueId);
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
