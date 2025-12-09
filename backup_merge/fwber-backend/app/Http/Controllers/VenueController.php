<?php

namespace App\Http\Controllers;

use App\Http\Requests\VenueSearchRequest;
use App\Models\Venue;
use App\Models\VenueCheckin;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class VenueController extends Controller
{
    /**
     * Get venues near a location
     *
     * @OA\Get(
     *   path="/venues",
     *   tags={"Venues"},
     *   summary="List nearby venues",
     *   @OA\Parameter(name="lat", in="query", required=true, @OA\Schema(type="number", format="float")),
     *   @OA\Parameter(name="lng", in="query", required=true, @OA\Schema(type="number", format="float")),
     *   @OA\Parameter(name="radius", in="query", required=false, @OA\Schema(type="integer", minimum=100, maximum=50000)),
     *   @OA\Response(response=200, description="Nearby venues",
     *     @OA\JsonContent(type="object",
     *       @OA\Property(property="venues", type="array", @OA\Items(type="object")),
     *       @OA\Property(property="user_location", type="object")
     *     )
     *   )
     * )
     */
    public function index(VenueSearchRequest $request): JsonResponse
    {
        $lat = (float) $request->validated('lat');
        $lng = (float) $request->validated('lng');
        $radius = (int) $request->validated('radius', 10000); // Default 10km

        $haversine = '(6371000 * acos(cos(radians(?)) * cos(radians(latitude)) * cos(radians(longitude) - radians(?)) + sin(radians(?)) * sin(radians(latitude))))';

        // Use Haversine formula for distance
        $venues = Venue::select('*')
            ->selectRaw("{$haversine} AS distance", [$lat, $lng, $lat])
            ->whereRaw("{$haversine} <= ?", [$lat, $lng, $lat, $radius])
            ->where('is_active', true)
            ->orderBy('distance')
            ->limit(20)
            ->get();

        // Append active check-in count to each venue
        $venues->each(function ($venue) {
            $venue->active_checkins = $venue->checkins()
                ->whereNull('checked_out_at')
                ->where('created_at', '>=', now()->subHours(12)) // Auto-checkout after 12h logic
                ->count();
        });

        return response()->json([
            'venues' => $venues,
            'user_location' => ['lat' => $lat, 'lng' => $lng],
            'search_radius' => $radius,
        ]);
    }

    /**
     * Get a specific venue details
     *
     * @OA\Get(
     *   path="/venues/{id}",
     *   tags={"Venues"},
     *   summary="Get venue details",
     *   @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *   @OA\Response(response=200, description="Venue details")
     * )
     */
    public function show(int $id): JsonResponse
    {
        $venue = Venue::findOrFail($id);
        
        // Get active checkins with user profiles (limited info)
        $activeCheckins = $venue->checkins()
            ->with(['user.profile'])
            ->whereNull('checked_out_at')
            ->where('created_at', '>=', now()->subHours(12))
            ->orderBy('created_at', 'desc')
            ->limit(50)
            ->get()
            ->map(function ($checkin) {
                return [
                    'user_id' => $checkin->user_id,
                    'name' => $checkin->user->name, // Or display name
                    'avatar' => $checkin->user->profile->avatar_url ?? null,
                    'checked_in_at' => $checkin->created_at,
                    'message' => $checkin->message,
                ];
            });

        $venue->active_checkins_count = $activeCheckins->count();
        $venue->recent_checkins = $activeCheckins;

        return response()->json(['venue' => $venue]);
    }
}
