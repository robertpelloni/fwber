<?php

namespace App\Http\Controllers;

use App\Http\Requests\FindNearbyChatroomsRequest;
use App\Http\Requests\JoinProximityChatroomRequest;
use App\Http\Requests\NearbyNetworkingRequest;
use App\Http\Requests\StoreProximityChatroomRequest;
use App\Http\Requests\UpdateChatroomLocationRequest;
use App\Models\ProximityChatroom;
use App\Models\User;
use App\Services\ContentModerationService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class ProximityChatroomController extends Controller
{
    protected $contentModeration;

    public function __construct(ContentModerationService $contentModeration)
    {
        $this->contentModeration = $contentModeration;
    }

    /**
     * Find nearby proximity chatrooms
     *
     * @OA\Get(
     *   path="/proximity-chatrooms/nearby",
     *   tags={"Chatrooms"},
     *   summary="Find nearby proximity chatrooms",
     *   security={{"bearerAuth":{}}},
     *   @OA\Parameter(name="latitude", in="query", required=true, @OA\Schema(type="number", format="float", minimum=-90, maximum=90)),
     *   @OA\Parameter(name="longitude", in="query", required=true, @OA\Schema(type="number", format="float", minimum=-180, maximum=180)),
     *   @OA\Parameter(name="radius_meters", in="query", required=false, @OA\Schema(type="integer", minimum=50, maximum=5000)),
     *   @OA\Parameter(name="type", in="query", required=false, @OA\Schema(type="string", enum={"conference","event","venue","area","temporary"})),
     *   @OA\Parameter(name="venue_type", in="query", required=false, @OA\Schema(type="string")),
     *   @OA\Response(response=200, description="Nearby rooms")
     * )
     */
    public function findNearby(FindNearbyChatroomsRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $latitude = $validated['latitude'];
        $longitude = $validated['longitude'];
        $radiusMeters = $validated['radius_meters'] ?? 1000;
        $type = $validated['type'] ?? null;
        $venueType = $validated['venue_type'] ?? null;
        $tags = $validated['tags'] ?? [];

        $query = ProximityChatroom::active()->public()
            ->withinRadius($latitude, $longitude, $radiusMeters);

        if ($type) {
            $query->byType($type);
        }

        if ($venueType) {
            $query->byVenueType($venueType);
        }

        if (!empty($tags)) {
            $query->whereJsonContains('tags', $tags);
        }

        // Check if user is a member
        $query->withExists(['members as is_member' => function ($q) {
            $q->where('user_id', Auth::id());
        }]);

        $chatrooms = $query->with(['creator', 'activeMembers'])
            ->orderBy('distance')
            ->limit(20)
            ->get();

        // Add distance information to each chatroom
        $chatrooms->each(function ($chatroom) use ($latitude, $longitude) {
            $chatroom->distance_meters = $chatroom->calculateDistance($latitude, $longitude);
        });

        return response()->json([
            'chatrooms' => $chatrooms,
            'user_location' => [
                'latitude' => $latitude,
                'longitude' => $longitude,
            ],
            'search_radius' => $radiusMeters,
        ]);
    }

    /**
     * Create a proximity chatroom
     *
     * @OA\Post(
     *   path="/proximity-chatrooms",
     *   tags={"Chatrooms"},
     *   summary="Create proximity chatroom",
     *   security={{"bearerAuth":{}}},
     *   @OA\RequestBody(required=true, @OA\JsonContent(
     *     required={"name","type","latitude","longitude"},
     *     @OA\Property(property="name", type="string", maxLength=100),
     *     @OA\Property(property="description", type="string", maxLength=500),
     *     @OA\Property(property="type", type="string", enum={"conference","event","venue","area","temporary"}),
     *     @OA\Property(property="venue_name", type="string"),
     *     @OA\Property(property="venue_type", type="string"),
     *     @OA\Property(property="event_name", type="string"),
     *     @OA\Property(property="event_date", type="string", format="date"),
     *     @OA\Property(property="event_start_time", type="string", format="time"),
     *     @OA\Property(property="event_end_time", type="string", format="time"),
     *     @OA\Property(property="latitude", type="number", format="float"),
     *     @OA\Property(property="longitude", type="number", format="float"),
     *     @OA\Property(property="radius_meters", type="integer"),
     *     @OA\Property(property="city", type="string"),
     *     @OA\Property(property="neighborhood", type="string"),
     *     @OA\Property(property="address", type="string"),
     *     @OA\Property(property="tags", type="array", @OA\Items(type="string")),
     *     @OA\Property(property="max_members", type="integer"),
     *     @OA\Property(property="requires_approval", type="boolean"),
     *     @OA\Property(property="expires_at", type="string", format="date-time")
     *   )),
     *   @OA\Response(response=201, description="Created")
     * )
     */
    public function create(StoreProximityChatroomRequest $request): JsonResponse
    {
        $validated = $request->validated();

        // Generate geohash for efficient proximity queries
        $geohash = $this->generateGeohash($validated['latitude'], $validated['longitude']);

        $chatroom = ProximityChatroom::create([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'type' => $validated['type'],
            'venue_name' => $validated['venue_name'] ?? null,
            'venue_type' => $validated['venue_type'] ?? null,
            'event_name' => $validated['event_name'] ?? null,
            'event_date' => $validated['event_date'] ?? null,
            'event_start_time' => $validated['event_start_time'] ?? null,
            'event_end_time' => $validated['event_end_time'] ?? null,
            'latitude' => $validated['latitude'],
            'longitude' => $validated['longitude'],
            'radius_meters' => $validated['radius_meters'] ?? 100,
            'geohash' => $geohash,
            'city' => $validated['city'] ?? null,
            'neighborhood' => $validated['neighborhood'] ?? null,
            'address' => $validated['address'] ?? null,
            'tags' => $validated['tags'] ?? [],
            'created_by' => Auth::id(),
            'is_active' => true,
            'is_public' => true,
            'requires_approval' => $validated['requires_approval'] ?? false,
            'max_members' => $validated['max_members'] ?? null,
            'current_members' => 1,
            'message_count' => 0,
            'last_activity_at' => now(),
            'expires_at' => $validated['expires_at'] ?? null,
        ]);

        // Add creator as admin member
        $chatroom->addMember(Auth::user(), [
            'latitude' => $validated['latitude'],
            'longitude' => $validated['longitude'],
            'distance_meters' => 0,
        ]);

        Log::info('Proximity chatroom created', [
            'chatroom_id' => $chatroom->id,
            'created_by' => Auth::id(),
            'type' => $chatroom->type,
            'location' => "{$validated['latitude']}, {$validated['longitude']}",
        ]);

        return response()->json($chatroom->load('creator'), 201);
    }

    /**
     * Get a specific proximity chatroom
     *
     * @OA\Get(
     *   path="/proximity-chatrooms/{id}",
     *   tags={"Chatrooms"},
     *   summary="Get proximity chatroom",
     *   security={{"bearerAuth":{}}},
     *   @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *   @OA\Parameter(name="latitude", in="query", required=false, @OA\Schema(type="number")),
     *   @OA\Parameter(name="longitude", in="query", required=false, @OA\Schema(type="number")),
     *   @OA\Response(response=200, description="Chatroom")
     * )
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $chatroom = ProximityChatroom::with(['creator', 'activeMembers'])
            ->findOrFail($id);

        // Check if user is within proximity
        if ($request->has('latitude') && $request->has('longitude')) {
            $isWithinProximity = $chatroom->isWithinProximity(
                $request->latitude,
                $request->longitude
            );
            $chatroom->is_within_proximity = $isWithinProximity;
        }

        return response()->json($chatroom);
    }

    /**
     * Join a proximity chatroom
     *
     * @OA\Post(
     *   path="/proximity-chatrooms/{id}/join",
     *   tags={"Chatrooms"},
     *   summary="Join proximity chatroom",
     *   security={{"bearerAuth":{}}},
     *   @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *   @OA\RequestBody(required=true, @OA\JsonContent(
     *     required={"latitude","longitude"},
     *     @OA\Property(property="latitude", type="number", format="float"),
     *     @OA\Property(property="longitude", type="number", format="float"),
     *     @OA\Property(property="is_networking", type="boolean"),
     *     @OA\Property(property="is_social", type="boolean"),
     *     @OA\Property(property="professional_info", type="object"),
     *     @OA\Property(property="interests", type="array", @OA\Items(type="string"))
     *   )),
     *   @OA\Response(response=200, description="Joined"),
    *   @OA\Response(response=403, ref="#/components/responses/Forbidden"),
     *   @OA\Response(response=400, description="Already member")
     * )
     */
    public function join(JoinProximityChatroomRequest $request, int $id): JsonResponse
    {
        $validated = $request->validated();
        $chatroom = ProximityChatroom::findOrFail($id);

        // Check if user is within proximity
        if (!$chatroom->isWithinProximity($validated['latitude'], $validated['longitude'])) {
            return response()->json([
                'message' => 'You are not within the proximity of this chatroom',
                'required_radius' => $chatroom->radius_meters,
            ], 403);
        }

        // Check if already a member
        if ($chatroom->hasMember(Auth::user())) {
            return response()->json(['message' => 'You are already a member of this chatroom'], 400);
        }

        // Check if user is banned
        if ($chatroom->isBanned(Auth::user())) {
            return response()->json(['message' => 'You are banned from this chatroom'], 403);
        }

        // Check if chatroom is full
        if ($chatroom->max_members && $chatroom->current_members >= $chatroom->max_members) {
            return response()->json(['message' => 'This chatroom is full'], 403);
        }

        // Add user as member with location and preferences
        $chatroom->addMember(Auth::user(), [
            'latitude' => $validated['latitude'],
            'longitude' => $validated['longitude'],
            'distance_meters' => $chatroom->calculateDistance($validated['latitude'], $validated['longitude']),
            'is_networking' => $validated['is_networking'] ?? false,
            'is_social' => $validated['is_social'] ?? true,
            'professional_info' => $validated['professional_info'] ?? [],
            'interests' => $validated['interests'] ?? [],
        ]);

        Log::info('User joined proximity chatroom', [
            'chatroom_id' => $chatroom->id,
            'user_id' => Auth::id(),
            'location' => "{$validated['latitude']}, {$validated['longitude']}",
        ]);

        return response()->json(['message' => 'Successfully joined proximity chatroom']);
    }

    /**
     * Leave a proximity chatroom
     *
     * @OA\Post(
     *   path="/proximity-chatrooms/{id}/leave",
     *   tags={"Chatrooms"},
     *   summary="Leave proximity chatroom",
     *   security={{"bearerAuth":{}}},
     *   @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *   @OA\Response(response=200, description="Left"),
     *   @OA\Response(response=400, description="Not a member")
     * )
     */
    public function leave(Request $request, int $id): JsonResponse
    {
        $chatroom = ProximityChatroom::findOrFail($id);

        if (!$chatroom->hasMember(Auth::user())) {
            return response()->json(['message' => 'You are not a member of this chatroom'], 400);
        }

        $chatroom->removeMember(Auth::user());

        Log::info('User left proximity chatroom', [
            'chatroom_id' => $chatroom->id,
            'user_id' => Auth::id(),
        ]);

        return response()->json(['message' => 'Successfully left proximity chatroom']);
    }

    /**
     * Update member location
     *
     * @OA\Post(
     *   path="/proximity-chatrooms/{id}/location",
     *   tags={"Chatrooms"},
     *   summary="Update member location",
     *   security={{"bearerAuth":{}}},
     *   @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *   @OA\RequestBody(required=true, @OA\JsonContent(
     *     required={"latitude","longitude"},
     *     @OA\Property(property="latitude", type="number", format="float"),
     *     @OA\Property(property="longitude", type="number", format="float")
     *   )),
     *   @OA\Response(response=200, description="Updated"),
    *   @OA\Response(response=403, ref="#/components/responses/Forbidden")
     * )
     */
    public function updateLocation(UpdateChatroomLocationRequest $request, int $id): JsonResponse
    {
        $validated = $request->validated();
        $chatroom = ProximityChatroom::findOrFail($id);

        if (!$chatroom->hasMember(Auth::user())) {
            return response()->json(['message' => 'You are not a member of this chatroom'], 403);
        }

        // Check if still within proximity
        if (!$chatroom->isWithinProximity($validated['latitude'], $validated['longitude'])) {
            return response()->json([
                'message' => 'You are no longer within the proximity of this chatroom',
                'required_radius' => $chatroom->radius_meters,
            ], 403);
        }

        $chatroom->updateMemberLocation(
            Auth::user(),
            $validated['latitude'],
            $validated['longitude']
        );

        return response()->json(['message' => 'Location updated successfully']);
    }

    /**
     * Get proximity chatroom members
     *
     * @OA\Get(
     *   path="/proximity-chatrooms/{id}/members",
     *   tags={"Chatrooms"},
     *   summary="Members",
     *   security={{"bearerAuth":{}}},
     *   @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *   @OA\Parameter(name="networking_only", in="query", required=false, @OA\Schema(type="boolean")),
     *   @OA\Parameter(name="social_only", in="query", required=false, @OA\Schema(type="boolean")),
     *   @OA\Response(response=200, description="Paginated members")
     * )
     */
    public function members(Request $request, int $id): JsonResponse
    {
        $chatroom = ProximityChatroom::findOrFail($id);

        if (!$chatroom->hasMember(Auth::user())) {
            return response()->json(['message' => 'You are not a member of this chatroom'], 403);
        }

        $query = $chatroom->activeMembers();

        // Filter by networking/social preferences
        if ($request->has('networking_only')) {
            $query->wherePivot('is_networking', true);
        }

        if ($request->has('social_only')) {
            $query->wherePivot('is_social', true);
        }

        $members = $query->with(['user'])
            ->orderBy('pivot_joined_at', 'desc')
            ->paginate(50);

        return response()->json($members);
    }

    /**
     * Get nearby networking members
     *
     * @OA\Get(
     *   path="/proximity-chatrooms/{id}/networking",
     *   tags={"Chatrooms"},
     *   summary="Nearby networking members",
     *   security={{"bearerAuth":{}}},
     *   @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *   @OA\Parameter(name="latitude", in="query", required=true, @OA\Schema(type="number", format="float")),
     *   @OA\Parameter(name="longitude", in="query", required=true, @OA\Schema(type="number", format="float")),
     *   @OA\Parameter(name="radius_meters", in="query", required=false, @OA\Schema(type="integer")),
     *   @OA\Response(response=200, description="Members within radius")
     * )
     */
    public function nearbyNetworking(NearbyNetworkingRequest $request, int $id): JsonResponse
    {
        $validated = $request->validated();
        $chatroom = ProximityChatroom::findOrFail($id);

        if (!$chatroom->hasMember(Auth::user())) {
            return response()->json(['message' => 'You are not a member of this chatroom'], 403);
        }

        $radiusMeters = $validated['radius_meters'] ?? 100;
        $latitude = $validated['latitude'];
        $longitude = $validated['longitude'];

        $members = $chatroom->networkingMembers()
            ->wherePivot('is_visible', true)
            ->get()
            ->filter(function ($member) use ($latitude, $longitude, $radiusMeters) {
                $distance = $this->calculateDistance(
                    $latitude,
                    $longitude,
                    $member->pivot->latitude,
                    $member->pivot->longitude
                );
                return $distance <= $radiusMeters;
            })
            ->map(function ($member) use ($latitude, $longitude) {
                $member->distance_meters = $this->calculateDistance(
                    $latitude,
                    $longitude,
                    $member->pivot->latitude,
                    $member->pivot->longitude
                );
                return $member;
            })
            ->sortBy('distance_meters');

        return response()->json([
            'members' => $members,
            'user_location' => [
                'latitude' => $latitude,
                'longitude' => $longitude,
            ],
            'search_radius' => $radiusMeters,
        ]);
    }

    /**
     * Get proximity chatroom analytics
     *
     * @OA\Get(
     *   path="/proximity-chatrooms/{id}/analytics",
     *   tags={"Chatrooms"},
     *   summary="Analytics (moderators)",
     *   security={{"bearerAuth":{}}},
     *   @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *   @OA\Response(response=200, description="Analytics data"),
    *   @OA\Response(response=403, ref="#/components/responses/Forbidden")
     * )
     */
    public function analytics(Request $request, int $id): JsonResponse
    {
        $chatroom = ProximityChatroom::findOrFail($id);

        if (!$chatroom->hasModerator(Auth::user())) {
            return response()->json(['message' => 'You do not have permission to view analytics'], 403);
        }

        $analytics = [
            'total_members' => $chatroom->current_members,
            'networking_members' => $chatroom->networkingMembers()->count(),
            'social_members' => $chatroom->socialMembers()->count(),
            'visible_members' => $chatroom->visibleMembers()->count(),
            'total_messages' => $chatroom->message_count,
            'active_members' => $chatroom->activeMembers()
                ->wherePivot('last_seen_at', '>=', now()->subHours(24))
                ->count(),
            'average_distance' => $chatroom->activeMembers()
                ->whereNotNull('pivot_distance_meters')
                ->avg('pivot_distance_meters'),
        ];

        return response()->json($analytics);
    }

    /**
     * Generate geohash for location
     */
    private function generateGeohash(float $latitude, float $longitude): string
    {
        // Simple geohash implementation (in production, use a proper geohash library)
        $precision = 8;
        $latRange = [-90, 90];
        $lonRange = [-180, 180];
        
        $geohash = '';
        $isEven = true;
        $bit = 0;
        $ch = 0;
        
        while (strlen($geohash) < $precision) {
            if ($isEven) {
                $mid = ($lonRange[0] + $lonRange[1]) / 2;
                if ($longitude >= $mid) {
                    $ch |= (1 << (4 - $bit));
                    $lonRange[0] = $mid;
                } else {
                    $lonRange[1] = $mid;
                }
            } else {
                $mid = ($latRange[0] + $latRange[1]) / 2;
                if ($latitude >= $mid) {
                    $ch |= (1 << (4 - $bit));
                    $latRange[0] = $mid;
                } else {
                    $latRange[1] = $mid;
                }
            }
            
            $isEven = !$isEven;
            
            if ($bit < 4) {
                $bit++;
            } else {
                $geohash .= $this->base32Encode($ch);
                $bit = 0;
                $ch = 0;
            }
        }
        
        return $geohash;
    }

    /**
     * Base32 encoding for geohash
     */
    private function base32Encode(int $value): string
    {
        $base32 = '0123456789bcdefghjkmnpqrstuvwxyz';
        return $base32[$value];
    }

    /**
     * Calculate distance between two points
     */
    private function calculateDistance(float $lat1, float $lon1, float $lat2, float $lon2): int
    {
        $earthRadius = 6371000; // Earth's radius in meters
        
        $lat1 = deg2rad($lat1);
        $lon1 = deg2rad($lon1);
        $lat2 = deg2rad($lat2);
        $lon2 = deg2rad($lon2);
        
        $dlat = $lat2 - $lat1;
        $dlon = $lon2 - $lon1;
        
        $a = sin($dlat/2) * sin($dlat/2) + cos($lat1) * cos($lat2) * sin($dlon/2) * sin($dlon/2);
        $c = 2 * atan2(sqrt($a), sqrt(1-$a));
        
        return round($earthRadius * $c);
    }
}
