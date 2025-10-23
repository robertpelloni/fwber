<?php

namespace App\Http\Controllers;

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
     */
    public function findNearby(Request $request): JsonResponse
    {
        $request->validate([
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'radius_meters' => 'nullable|integer|min:50|max:5000',
            'type' => 'nullable|in:conference,event,venue,area,temporary',
            'venue_type' => 'nullable|string|max:50',
            'tags' => 'nullable|array',
        ]);

        $latitude = $request->latitude;
        $longitude = $request->longitude;
        $radiusMeters = $request->get('radius_meters', 1000);
        $type = $request->get('type');
        $venueType = $request->get('venue_type');
        $tags = $request->get('tags', []);

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
     */
    public function create(Request $request): JsonResponse
    {
        $request->validate([
            'name' => 'required|string|max:100',
            'description' => 'nullable|string|max:500',
            'type' => 'required|in:conference,event,venue,area,temporary',
            'venue_name' => 'nullable|string|max:100',
            'venue_type' => 'nullable|string|max:50',
            'event_name' => 'nullable|string|max:100',
            'event_date' => 'nullable|date',
            'event_start_time' => 'nullable|date_format:H:i',
            'event_end_time' => 'nullable|date_format:H:i',
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'radius_meters' => 'nullable|integer|min:50|max:1000',
            'city' => 'nullable|string|max:100',
            'neighborhood' => 'nullable|string|max:100',
            'address' => 'nullable|string|max:200',
            'tags' => 'nullable|array',
            'max_members' => 'nullable|integer|min:2|max:1000',
            'requires_approval' => 'boolean',
            'expires_at' => 'nullable|date|after:now',
        ]);

        // Generate geohash for efficient proximity queries
        $geohash = $this->generateGeohash($request->latitude, $request->longitude);

        $chatroom = ProximityChatroom::create([
            'name' => $request->name,
            'description' => $request->description,
            'type' => $request->type,
            'venue_name' => $request->venue_name,
            'venue_type' => $request->venue_type,
            'event_name' => $request->event_name,
            'event_date' => $request->event_date,
            'event_start_time' => $request->event_start_time,
            'event_end_time' => $request->event_end_time,
            'latitude' => $request->latitude,
            'longitude' => $request->longitude,
            'radius_meters' => $request->get('radius_meters', 100),
            'geohash' => $geohash,
            'city' => $request->city,
            'neighborhood' => $request->neighborhood,
            'address' => $request->address,
            'tags' => $request->tags ?? [],
            'created_by' => Auth::id(),
            'is_active' => true,
            'is_public' => true,
            'requires_approval' => $request->get('requires_approval', false),
            'max_members' => $request->max_members,
            'current_members' => 1,
            'message_count' => 0,
            'last_activity_at' => now(),
            'expires_at' => $request->expires_at,
        ]);

        // Add creator as admin member
        $chatroom->addMember(Auth::user(), [
            'latitude' => $request->latitude,
            'longitude' => $request->longitude,
            'distance_meters' => 0,
        ]);

        Log::info('Proximity chatroom created', [
            'chatroom_id' => $chatroom->id,
            'created_by' => Auth::id(),
            'type' => $chatroom->type,
            'location' => "{$request->latitude}, {$request->longitude}",
        ]);

        return response()->json($chatroom->load('creator'), 201);
    }

    /**
     * Get a specific proximity chatroom
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
     */
    public function join(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'is_networking' => 'boolean',
            'is_social' => 'boolean',
            'professional_info' => 'nullable|array',
            'interests' => 'nullable|array',
        ]);

        $chatroom = ProximityChatroom::findOrFail($id);

        // Check if user is within proximity
        if (!$chatroom->isWithinProximity($request->latitude, $request->longitude)) {
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
            'latitude' => $request->latitude,
            'longitude' => $request->longitude,
            'distance_meters' => $chatroom->calculateDistance($request->latitude, $request->longitude),
            'is_networking' => $request->get('is_networking', false),
            'is_social' => $request->get('is_social', true),
            'professional_info' => $request->professional_info ?? [],
            'interests' => $request->interests ?? [],
        ]);

        Log::info('User joined proximity chatroom', [
            'chatroom_id' => $chatroom->id,
            'user_id' => Auth::id(),
            'location' => "{$request->latitude}, {$request->longitude}",
        ]);

        return response()->json(['message' => 'Successfully joined proximity chatroom']);
    }

    /**
     * Leave a proximity chatroom
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
     */
    public function updateLocation(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
        ]);

        $chatroom = ProximityChatroom::findOrFail($id);

        if (!$chatroom->hasMember(Auth::user())) {
            return response()->json(['message' => 'You are not a member of this chatroom'], 403);
        }

        // Check if still within proximity
        if (!$chatroom->isWithinProximity($request->latitude, $request->longitude)) {
            return response()->json([
                'message' => 'You are no longer within the proximity of this chatroom',
                'required_radius' => $chatroom->radius_meters,
            ], 403);
        }

        $chatroom->updateMemberLocation(
            Auth::user(),
            $request->latitude,
            $request->longitude
        );

        return response()->json(['message' => 'Location updated successfully']);
    }

    /**
     * Get proximity chatroom members
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
     */
    public function nearbyNetworking(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'radius_meters' => 'nullable|integer|min:50|max:1000',
        ]);

        $chatroom = ProximityChatroom::findOrFail($id);

        if (!$chatroom->hasMember(Auth::user())) {
            return response()->json(['message' => 'You are not a member of this chatroom'], 403);
        }

        $radiusMeters = $request->get('radius_meters', 100);
        $latitude = $request->latitude;
        $longitude = $request->longitude;

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
