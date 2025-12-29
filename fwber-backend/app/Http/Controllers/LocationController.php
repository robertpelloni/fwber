<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateLocationRequest;
use App\Http\Requests\GetNearbyUsersRequest;
use App\Http\Requests\UpdateLocationPrivacyRequest;
use App\Models\UserLocation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Location Controller - Real-time Location Tracking API

 * 
 * AI Model: Claude 4.5 - Multi-AI Orchestration
 * Phase: Location-Based Social Features Implementation
 * Purpose: Handle location tracking and proximity-based discovery
 * 
 * Created: 2025-01-19
 */
class LocationController extends Controller
{
    /**
     * Update user's current location
    *
    * @OA\Post(
    *   path="/location",
    *   tags={"Location"},
    *   summary="Update current location",
    *   description="Updates the authenticated user's current location and privacy level.",
    *   security={{"bearerAuth":{}}},
    *   @OA\RequestBody(required=true,
    *     @OA\JsonContent(
    *       required={"latitude","longitude"},
    *       @OA\Property(property="latitude", type="number", format="float", example=37.7749),
    *       @OA\Property(property="longitude", type="number", format="float", example=-122.4194),
    *       @OA\Property(property="accuracy", type="number", example=12.5),
    *       @OA\Property(property="heading", type="number", example=140),
    *       @OA\Property(property="speed", type="number", example=1.2),
    *       @OA\Property(property="altitude", type="number", example=20),
    *       @OA\Property(property="privacy_level", type="string", enum={"public","friends","private"})
    *     )
    *   ),
    *   @OA\Response(response=200, description="Updated"),
    *   @OA\Response(response=422, ref="#/components/responses/ValidationError"),
    *   @OA\Response(response=401, description="Unauthenticated")
    * )
     */
    public function update(UpdateLocationRequest $request): JsonResponse
    {
        try {
            $user = auth()->user();
            
            if (!$user) {
                return response()->json(['message' => 'Unauthenticated'], 401);
            }

            // Get or create user location
            $location = UserLocation::where('user_id', $user->id)->first();
            
            if (!$location) {
                $location = new UserLocation();
                $location->user_id = $user->id;
            }

            // Update location data
            $location->fill($request->validated());
            
            $location->last_updated = now();
            $location->is_active = true;
            $location->save();

            Log::info('Location updated', [
                'user_id' => $user->id,
                'latitude' => $location->latitude,
                'longitude' => $location->longitude,
                'privacy_level' => $location->privacy_level,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Location updated successfully',
                'data' => [
                    'id' => $location->id,
                    'latitude' => $location->latitude,
                    'longitude' => $location->longitude,
                    'accuracy' => $location->accuracy,
                    'privacy_level' => $location->privacy_level,
                    'last_updated' => $location->last_updated,
                    'is_active' => $location->is_active,
                ],
            ]);

        } catch (\Exception $e) {

            Log::error('Location update error', [
                'user_id' => auth()->id(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'message' => 'Error updating location',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    /**
     * Get nearby users within radius
        *
        * @OA\Get(
        *   path="/location/nearby",
        *   tags={"Location"},
        *   summary="Find nearby users",
        *   description="Finds nearby users within a radius considering privacy settings.",
        *   security={{"bearerAuth":{}}},
        *   @OA\Parameter(name="latitude", in="query", required=true, @OA\Schema(type="number", format="float")),
        *   @OA\Parameter(name="longitude", in="query", required=true, @OA\Schema(type="number", format="float")),
        *   @OA\Parameter(name="radius", in="query", required=false, @OA\Schema(type="integer", minimum=100, maximum=10000)),
        *   @OA\Parameter(name="limit", in="query", required=false, @OA\Schema(type="integer", minimum=1, maximum=100)),
        *   @OA\Response(response=200, description="Nearby users list"),
        *   @OA\Response(response=422, ref="#/components/responses/ValidationError"),
        *   @OA\Response(response=401, description="Unauthenticated")
        * )
     */
    public function nearby(GetNearbyUsersRequest $request): JsonResponse
    {
        try {
            $user = auth()->user();
            
            if (!$user) {
                return response()->json(['message' => 'Unauthenticated'], 401);
            }

            $validated = $request->validated();
            $latitude = $validated['latitude'];
            $longitude = $validated['longitude'];
            $radius = $validated['radius'] ?? 1000; // Default 1km
            $limit = $validated['limit'] ?? 20; // Default 20 users

            // Get nearby users based on privacy settings
            $nearbyUsers = UserLocation::active()

                ->with(['user.profile'])
                ->where('user_id', '!=', $user->id) // Exclude current user
                // Incognito Mode Logic
                ->whereHas('user', function ($userQuery) use ($user) {
                    $userQuery->where(function ($q) use ($user) {
                        // Case 1: Not Incognito
                        $q->whereHas('profile', function ($p) {
                            $p->where('is_incognito', false);
                        })
                        // Case 2: Incognito but Liked Me
                        ->orWhere(function ($q) use ($user) {
                            $q->whereHas('profile', function ($p) {
                                $p->where('is_incognito', true);
                            })
                            ->whereHas('matchActions', function ($ma) use ($user) {
                                $ma->where('target_user_id', $user->id)
                                   ->whereIn('action', ['like', 'super_like']);
                            });
                        });
                    });
                })
                ->where(function ($query) use ($user) {
                    // Show public locations to everyone
                    $query->where('privacy_level', 'public')
                        // Show friends-only locations to matched users
                        ->orWhere(function ($subQuery) use ($user) {
                            $subQuery->where('privacy_level', 'friends')
                                ->whereHas('user', function ($userQuery) use ($user) {
                                    $userQuery->where(function ($q) use ($user) {
                                        $q->whereHas('matchesAsUser1', function ($mq) use ($user) {
                                            $mq->where('user2_id', $user->id);
                                        })
                                        ->orWhereHas('matchesAsUser2', function ($mq) use ($user) {
                                            $mq->where('user1_id', $user->id);
                                        });
                                    });
                                });
                        });
                })
                ->withinRadius($latitude, $longitude, $radius)
                ->limit($limit)
                ->get();

            // Format response data
            $formattedUsers = $nearbyUsers->map(function ($location) {
                return [
                    'id' => $location->user->id,
                    'display_name' => $location->user->profile?->display_name ?? $location->user->email,
                    'age' => $location->user->profile?->age,
                    'gender' => $location->user->profile?->gender,
                    'location' => [
                        'latitude' => $location->latitude,
                        'longitude' => $location->longitude,
                        'accuracy' => $location->accuracy,
                        'distance' => $location->formatted_distance,
                        'last_updated' => $location->last_updated,
                    ],
                    'privacy_level' => $location->privacy_level,
                    'is_recent' => $location->isRecent(),
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $formattedUsers,
                'meta' => [
                    'total' => $formattedUsers->count(),
                    'radius' => $radius,
                    'center' => [
                        'latitude' => $latitude,
                        'longitude' => $longitude,
                    ],
                ],
            ]);

        } catch (\Exception $e) {
            Log::error('Nearby users error', [
                'user_id' => auth()->id(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'message' => 'Error fetching nearby users',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    /**
     * Update location privacy settings
        *
        * @OA\Put(
        *   path="/location/privacy",
        *   tags={"Location"},
        *   summary="Update location privacy",
        *   security={{"bearerAuth":{}}},
        *   @OA\RequestBody(required=true,
        *     @OA\JsonContent(
        *       required={"privacy_level"},
        *       @OA\Property(property="privacy_level", type="string", enum={"public","friends","private"})
        *     )
        *   ),
        *   @OA\Response(response=200, description="Updated"),
        *   @OA\Response(response=404, ref="#/components/responses/NotFound"),
        *   @OA\Response(response=422, ref="#/components/responses/ValidationError"),
        *   @OA\Response(response=401, description="Unauthenticated")
        * )
     */
    public function updatePrivacy(UpdateLocationPrivacyRequest $request): JsonResponse
    {
        try {
            $user = auth()->user();
            
            if (!$user) {
                return response()->json(['message' => 'Unauthenticated'], 401);
            }

            $location = UserLocation::where('user_id', $user->id)->first();
            
            if (!$location) {
                return response()->json([
                    'message' => 'No location data found. Please update your location first.',
                ], 404);
            }

            $location->privacy_level = $request->privacy_level;
            $location->save();


            Log::info('Location privacy updated', [
                'user_id' => $user->id,
                'privacy_level' => $location->privacy_level,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Privacy settings updated successfully',
                'data' => [
                    'privacy_level' => $location->privacy_level,
                    'privacy_level_display' => $location->privacy_level_display,
                ],
            ]);

        } catch (\Exception $e) {
            Log::error('Location privacy update error', [
                'user_id' => auth()->id(),
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'Error updating privacy settings',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    /**
     * Clear location history (set inactive)
        *
        * @OA\Delete(
        *   path="/location",
        *   tags={"Location"},
        *   summary="Clear current location",
        *   security={{"bearerAuth":{}}},
        *   @OA\Response(response=200, description="Cleared"),
        *   @OA\Response(response=404, ref="#/components/responses/NotFound"),
        *   @OA\Response(response=401, description="Unauthenticated")
        * )
     */
    public function clear(Request $request): JsonResponse
    {
        try {
            $user = auth()->user();
            
            if (!$user) {
                return response()->json(['message' => 'Unauthenticated'], 401);
            }

            $location = UserLocation::where('user_id', $user->id)->first();
            
            if (!$location) {
                return response()->json([
                    'message' => 'No location data found.',
                ], 404);
            }

            $location->is_active = false;
            $location->save();

            Log::info('Location cleared', [
                'user_id' => $user->id,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Location history cleared successfully',
            ]);

        } catch (\Exception $e) {
            Log::error('Location clear error', [
                'user_id' => auth()->id(),
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'Error clearing location',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    /**
     * Get current user's location
        *
        * @OA\Get(
        *   path="/location",
        *   tags={"Location"},
        *   summary="Get current user's location",
        *   security={{"bearerAuth":{}}},
        *   @OA\Response(response=200, description="Location"),
        *   @OA\Response(response=404, ref="#/components/responses/NotFound"),
        *   @OA\Response(response=401, description="Unauthenticated")
        * )
     */
    public function show(Request $request): JsonResponse
    {
        try {
            $user = auth()->user();
            
            if (!$user) {
                return response()->json(['message' => 'Unauthenticated'], 401);
            }

            $location = UserLocation::where('user_id', $user->id)->first();
            
            if (!$location) {
                return response()->json([
                    'message' => 'No location data found. Please update your location.',
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $location->id,
                    'latitude' => $location->latitude,
                    'longitude' => $location->longitude,
                    'accuracy' => $location->accuracy,
                    'heading' => $location->heading,
                    'speed' => $location->speed,
                    'altitude' => $location->altitude,
                    'privacy_level' => $location->privacy_level,
                    'privacy_level_display' => $location->privacy_level_display,
                    'last_updated' => $location->last_updated,
                    'is_active' => $location->is_active,
                    'is_recent' => $location->isRecent(),
                ],
            ]);

        } catch (\Exception $e) {
            Log::error('Location show error', [
                'user_id' => auth()->id(),
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'Error fetching location',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error',
            ], 500);
        }
    }
}
