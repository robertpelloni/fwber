<?php

namespace App\Http\Controllers;

use App\Models\ProximityArtifact;
use App\Services\ProximityArtifactService;
use App\Services\ShadowThrottleService;
use App\Services\GeoSpoofDetectionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProximityArtifactController extends Controller
{
    public function __construct(
        private ShadowThrottleService $shadowThrottleService,
        private GeoSpoofDetectionService $geoSpoofService
    ) {}
    public function index(Request $request): JsonResponse
    {
        $user = auth()->user();
        $request->validate([
            'lat' => 'required|numeric',
            'lng' => 'required|numeric',
            'radius' => 'nullable|integer|min:100|max:10000',
            'type' => 'nullable|in:chat,board_post,announce',
        ]);

        $lat = (float)$request->lat;
        $lng = (float)$request->lng;
        $radius = (int)($request->radius ?? 1000);

        $q = ProximityArtifact::query()->active()->withinBox($lat, $lng, $radius);
        if ($request->filled('type')) {
            $q->type($request->type);
        }

        $artifacts = $q->orderByDesc('created_at')->limit(100)->get()
            ->map(function (ProximityArtifact $a) {
                return [
                    'id' => $a->id,
                    'type' => $a->type,
                    'content' => $a->content,
                    'lat' => $a->fuzzed_latitude,
                    'lng' => $a->fuzzed_longitude,
                    'radius' => $a->visibility_radius_m,
                    'expires_at' => $a->expires_at?->toIso8601String(),
                    'moderation_status' => $a->moderation_status,
                    'meta' => $a->meta,
                    'user_id' => $a->user_id,
                ];
            });

        return response()->json(['artifacts' => $artifacts]);
    }

    public function store(Request $request, ProximityArtifactService $service): JsonResponse
    {
        $user = auth()->user();
        $request->validate([
            'type' => 'required|in:chat,board_post,announce',
            'content' => 'required|string',
            'lat' => 'required|numeric',
            'lng' => 'required|numeric',
            'radius' => 'nullable|integer|min:100|max:10000',
        ]);

        // Detect potential geo-spoofing
        $ipAddress = $request->ip();
        $lat = (float)$request->lat;
        $lng = (float)$request->lng;
        
        $this->geoSpoofService->detectSpoof($user->id, $lat, $lng, $ipAddress);

        try {
            $artifact = $service->createArtifact($user, [
                'type' => $request->type,
                'content' => $request->content,
                'location_lat' => $lat,
                'location_lng' => $lng,
                'visibility_radius_m' => (int)($request->radius ?? 1000),
            ]);
        } catch (\Throwable $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }

        return response()->json(['artifact' => [
            'id' => $artifact->id,
            'type' => $artifact->type,
            'content' => $artifact->content,
            'lat' => $artifact->fuzzed_latitude,
            'lng' => $artifact->fuzzed_longitude,
            'radius' => $artifact->visibility_radius_m,
            'expires_at' => $artifact->expires_at?->toIso8601String(),
            'moderation_status' => $artifact->moderation_status,
            'meta' => $artifact->meta,
            'user_id' => $artifact->user_id,
        ]], 201);
    }

    public function show(int $id): JsonResponse
    {
        $artifact = ProximityArtifact::active()->findOrFail($id);
        return response()->json(['artifact' => [
            'id' => $artifact->id,
            'type' => $artifact->type,
            'content' => $artifact->content,
            'lat' => $artifact->fuzzed_latitude,
            'lng' => $artifact->fuzzed_longitude,
            'radius' => $artifact->visibility_radius_m,
            'expires_at' => $artifact->expires_at?->toIso8601String(),
            'moderation_status' => $artifact->moderation_status,
            'meta' => $artifact->meta,
            'user_id' => $artifact->user_id,
        ]]);
    }

    public function flag(int $id, Request $request, ProximityArtifactService $service): JsonResponse
    {
        $user = auth()->user();
        $artifact = ProximityArtifact::findOrFail($id);
        $service->flagArtifact($artifact, $user);
        return response()->json(['message' => 'Flag recorded']);
    }

    public function destroy(int $id): JsonResponse
    {
        $user = auth()->user();
        $artifact = ProximityArtifact::findOrFail($id);
        if ($artifact->user_id !== $user->id) {
            return response()->json(['error' => 'Forbidden'], 403);
        }
        $artifact->moderation_status = 'removed';
        $artifact->save();
        return response()->json(['message' => 'Removed']);
    }

    /**
     * Local Pulse - Merged feed of proximity artifacts + nearby match candidates
     * This combines the draw of hyperlocal ephemeral content with mutual-match discovery
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function localPulse(Request $request): JsonResponse
    {
        $user = auth()->user();
        $profile = $user->profile;

        if (!$profile) {
            return response()->json(['error' => 'Profile required'], 422);
        }

        $request->validate([
            'lat' => 'required|numeric',
            'lng' => 'required|numeric',
            'radius' => 'nullable|integer|min:100|max:10000',
        ]);

        $lat = (float)$request->lat;
        $lng = (float)$request->lng;
        $radius = (int)($request->radius ?? 1000);

        // Get proximity artifacts with shadow throttle filtering
        $artifacts = ProximityArtifact::query()
            ->active()
            ->withinBox($lat, $lng, $radius)
            ->orderByDesc('created_at')
            ->limit(100) // Get more, then filter and limit
            ->get()
            ->filter(function (ProximityArtifact $a) {
                // Apply shadow throttle probabilistic filtering
                $visibility = $this->shadowThrottleService->getVisibilityMultiplier($a->user_id);
                
                if ($visibility >= 1.0) {
                    return true; // Always show if not throttled
                }
                
                // Probabilistic inclusion based on visibility multiplier
                return (mt_rand() / mt_getrandmax()) < $visibility;
            })
            ->take(20) // Limit to 20 after filtering
            ->map(function (ProximityArtifact $a) {
                return [
                    'id' => $a->id,
                    'type' => $a->type,
                    'content' => $a->content,
                    'lat' => $a->fuzzed_latitude,
                    'lng' => $a->fuzzed_longitude,
                    'radius' => $a->visibility_radius_m,
                    'expires_at' => $a->expires_at?->toIso8601String(),
                    'created_at' => $a->created_at?->toIso8601String(),
                ];
            });

        // Get nearby match candidates (lightweight previews)
        $candidates = $this->getNearbyCompatibleCandidates($user, $profile, $lat, $lng, $radius, 10);

        return response()->json([
            'artifacts' => $artifacts,
            'candidates' => $candidates,
            'meta' => [
                'center_lat' => $lat,
                'center_lng' => $lng,
                'radius_m' => $radius,
                'artifacts_count' => $artifacts->count(),
                'candidates_count' => count($candidates),
            ],
        ]);
    }

    /**
     * Get nearby users with mutual wants compatibility
     * Returns lightweight candidate previews
     */
    private function getNearbyCompatibleCandidates(
        \App\Models\User $user,
        \App\Models\UserProfile $profile,
        float $lat,
        float $lng,
        int $radiusMeters,
        int $limit
    ): array {
        // Convert radius from meters to approximate degrees
        $radiusMiles = $radiusMeters / 1609.34;
        $latDist = (1.1 * $radiusMiles) / 69.0;
        $lonDist = (1.1 * $radiusMiles) / 69.1;

        // Get nearby users with profiles
        $query = \App\Models\User::query()
            ->where('id', '!=', $user->id)
            ->whereHas('profile', function ($q) use ($lat, $lng, $latDist, $lonDist) {
                $q->whereBetween('location_latitude', [$lat - $latDist, $lat + $latDist])
                  ->whereBetween('location_longitude', [$lng - $lonDist, $lng + $lonDist]);
            });

        // Gender preference filter
        if ($profile->preferences && isset($profile->preferences['gender_preferences'])) {
            $genderPrefs = $profile->preferences['gender_preferences'];
            $query->whereHas('profile', function ($q) use ($genderPrefs) {
                $q->whereIn('gender', array_keys(array_filter($genderPrefs)));
            });
        }

        // Age filter from preferences
        $ageMin = $profile->preferences['age_range']['min'] ?? 18;
        $ageMax = $profile->preferences['age_range']['max'] ?? 100;
        
        $query->whereHas('profile', function ($q) use ($ageMin, $ageMax) {
            $q->whereRaw("(julianday('now') - julianday(date_of_birth)) / 365.25 BETWEEN ? AND ?", [
                $ageMin,
                $ageMax
            ]);
        });

        // Exclude users already interacted with
        $excludedIds = \Illuminate\Support\Facades\DB::table('match_actions')
            ->where('user_id', $user->id)
            ->pluck('target_user_id')
            ->toArray();
        
        if (!empty($excludedIds)) {
            $query->whereNotIn('id', $excludedIds);
        }

        $candidates = $query->limit($limit * 2)->get() // Get extra to filter
            ->filter(function (\App\Models\User $candidate) {
                // Filter out shadow throttled users probabilistically
                $visibility = $this->shadowThrottleService->getVisibilityMultiplier($candidate->id);
                
                if ($visibility >= 1.0) {
                    return true;
                }
                
                return (mt_rand() / mt_getrandmax()) < $visibility;
            })
            ->take($limit);

        // Return lightweight previews (no full profiles, just teasers)
        return $candidates->map(function (\App\Models\User $candidate) use ($profile) {
            $candidateProfile = $candidate->profile;
            
            // Calculate simplified compatibility (just mutual wants indicators)
            $compatibilityIndicators = $this->getCompatibilityIndicators($profile, $candidateProfile);
            
            return [
                'user_id' => $candidate->id,
                'age' => $candidateProfile->date_of_birth?->diffInYears(now()),
                'gender' => $candidateProfile->gender,
                'distance_miles' => $this->calculateDistance($profile, $candidateProfile),
                'compatibility_indicators' => $compatibilityIndicators,
                'last_seen' => $candidate->last_seen_at?->diffForHumans(),
            ];
        })->toArray();
    }

    /**
     * Calculate distance between two profiles in miles
     */
    private function calculateDistance(\App\Models\UserProfile $profile1, \App\Models\UserProfile $profile2): float
    {
        if (!$profile1->location_latitude || !$profile2->location_latitude) {
            return 0;
        }

        $lat1 = $profile1->location_latitude;
        $lon1 = $profile1->location_longitude;
        $lat2 = $profile2->location_latitude;
        $lon2 = $profile2->location_longitude;

        $theta = $lon1 - $lon2;
        $dist = sin(deg2rad($lat1)) * sin(deg2rad($lat2)) + 
                cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * cos(deg2rad($theta));
        $dist = acos($dist);
        $dist = rad2deg($dist);
        $miles = $dist * 60 * 1.1515;

        return round($miles, 1);
    }

    /**
     * Get compatibility indicators showing mutual wants/preferences alignment
     */
    private function getCompatibilityIndicators(\App\Models\UserProfile $userProfile, \App\Models\UserProfile $candidateProfile): array
    {
        $indicators = [];

        // Check for shared interests/preferences
        if ($userProfile->preferences && $candidateProfile->preferences) {
            $userPrefs = $userProfile->preferences;
            $candidatePrefs = $candidateProfile->preferences;

            // Example indicators (adjust based on your preference schema)
            if (isset($userPrefs['relationship_type']) && isset($candidatePrefs['relationship_type'])) {
                $commonTypes = array_intersect(
                    array_keys(array_filter($userPrefs['relationship_type'] ?? [])),
                    array_keys(array_filter($candidatePrefs['relationship_type'] ?? []))
                );
                if (!empty($commonTypes)) {
                    $indicators[] = 'shared_relationship_goals';
                }
            }

            // Proximity engagement indicator
            $recentProximityPosts = \App\Models\ProximityArtifact::where('user_id', $candidateProfile->user_id)
                ->where('created_at', '>=', now()->subHours(24))
                ->count();
            if ($recentProximityPosts > 0) {
                $indicators[] = 'active_locally';
            }
        }

        return $indicators;
    }
}
