<?php

namespace App\Http\Controllers;

use App\Http\Requests\Profile\UpdateProfileRequest;
use App\Http\Resources\UserProfileResource;
use App\Models\User;
use App\Models\UserProfile;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

/**
 * Profile Controller - User Profile Management API
 * 
 * AI Model: Gemini 2.5 Flash - Simulated
 * Phase: 3A - First Next.js Integration
 * Purpose: Provide RESTful API for user profile operations
 * 
 * Created: 2025-10-18
 * Part of: Laravel + Next.js official stack (per ADR 001)
 */
class ProfileController extends Controller
{
    /**
     * @OA\Get(
     *     path="/users/{id}",
     *     tags={"Profile"},
     *     summary="Get public profile of a user",
     *     description="Retrieve public profile information for a specific user.",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="User ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Profile retrieved successfully",
     *         @OA\JsonContent(ref="#/components/schemas/UserProfileResource")
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="User not found"
     *     )
     * )
     */
    public function showPublic(int $id): JsonResponse
    {
        $user = User::with(['profile', 'photos'])->findOrFail($id);
        
        // Ensure profile exists
        if (!$user->profile) {
            return response()->json(['message' => 'Profile not found'], 404);
        }

        // Return resource (it handles privacy/sanitization)
        return response()->json([
            'data' => new UserProfileResource($user),
        ]);
    }

    /**
     * @OA\Get(
     *     path="/profile",
     *     tags={"Profile"},
     *     summary="Get authenticated user's profile",
     *     description="Retrieve complete profile information for the authenticated user including preferences, location, and completion status",
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Profile retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="name", type="string", example="John Doe"),
     *                 @OA\Property(property="email", type="string", example="john@example.com"),
     *                 @OA\Property(property="display_name", type="string", example="Johnny"),
     *                 @OA\Property(property="bio", type="string", example="Software developer passionate about technology"),
     *                 @OA\Property(property="date_of_birth", type="string", format="date", example="1990-05-15"),
     *                 @OA\Property(property="age", type="integer", example=33),
     *                 @OA\Property(property="gender", type="string", example="male"),
     *                 @OA\Property(property="avatar_url", type="string", nullable=true, example="https://cdn.fwber.com/avatars/123.jpg")
     *             ),
     *             @OA\Property(property="profile_complete", type="boolean", example=true)
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Profile not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Profile not found. Please complete your profile."),
     *             @OA\Property(property="profile_complete", type="boolean", example=false)
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated",
     *         @OA\JsonContent(ref="#/components/schemas/UnauthorizedError")
     *     )
     * )
     * 
     * Get authenticated user's profile
     * 
     * @return JsonResponse
     */
    public function show(Request $request): JsonResponse
    {
        try {
            $user = auth()->user();
            
            if (!$user) {
                return response()->json([
                    'message' => 'Unauthenticated',
                ], 401);
            }
            
            // Eager load profile relationship
            $user->load('profile');
            
            if (!$user->profile) {
                return response()->json([
                    'message' => 'Profile not found. Please complete your profile.',
                    'profile_complete' => false,
                ], 404);
            }
            
            return response()->json([
                'success' => true,
                'data' => new UserProfileResource($user),
                'profile_complete' => $this->isProfileComplete($user->profile),
            ]);
            
        } catch (\Exception $e) {
            Log::error('Profile fetch error', [
                'user_id' => auth()->id(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            return response()->json([
                'message' => 'Error fetching profile',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error',
            ], 500);
        }
    }
    
    /**
     * @OA\Put(
     *     path="/profile",
     *     tags={"Profile"},
     *     summary="Update user profile",
     *     description="Update profile information including bio, preferences, location, and personal details. All fields are optional.",
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="display_name", type="string", maxLength=50, example="Johnny"),
     *             @OA\Property(property="bio", type="string", maxLength=500, example="Updated bio text"),
     *             @OA\Property(property="date_of_birth", type="string", format="date", example="1990-05-15"),
     *             @OA\Property(property="gender", type="string", enum={"male", "female", "non-binary", "mtf", "ftm", "other", "prefer-not-to-say"}, example="male"),
     *             @OA\Property(property="pronouns", type="string", enum={"he/him", "she/her", "they/them", "he/they", "she/they", "other", "prefer-not-to-say"}, example="he/him"),
     *             @OA\Property(property="sexual_orientation", type="string", example="bisexual"),
     *             @OA\Property(property="relationship_style", type="string", example="non-monogamous"),
     *             @OA\Property(
     *                 property="looking_for",
     *                 type="array",
     *                 @OA\Items(type="string", enum={"friendship", "dating", "relationship", "casual", "marriage", "networking"}),
     *                 example={"dating", "relationship"}
     *             ),
     *             @OA\Property(
     *                 property="location",
     *                 type="object",
     *                 @OA\Property(property="latitude", type="number", format="float", example=40.7128),
     *                 @OA\Property(property="longitude", type="number", format="float", example=-74.0060),
     *                 @OA\Property(property="max_distance", type="integer", example=50),
     *                 @OA\Property(property="city", type="string", example="New York"),
     *                 @OA\Property(property="state", type="string", example="NY")
     *             ),
     *             @OA\Property(
     *                 property="preferences",
     *                 type="object",
     *                 @OA\Property(property="smoking", type="string", example="non-smoker"),
     *                 @OA\Property(property="drinking", type="string", example="occasional"),
     *                 @OA\Property(property="exercise", type="string", example="several-times-week"),
     *                 @OA\Property(property="age_range_min", type="integer", example=25),
     *                 @OA\Property(property="age_range_max", type="integer", example=40)
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Profile updated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Profile updated successfully"),
     *             @OA\Property(property="data", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(ref="#/components/schemas/ValidationError")
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated",
     *         @OA\JsonContent(ref="#/components/schemas/UnauthorizedError")
     *     )
     * )
     * 
     * Update authenticated user's profile
     * 
     * @param UpdateProfileRequest $request
     * @return JsonResponse
     */
    public function update(UpdateProfileRequest $request): JsonResponse
    {
        try {
            $user = auth()->user();
            
            if (!$user) {
                return response()->json(['message' => 'Unauthenticated'], 401);
            }
            
            $validated = $request->validated();
            
            // Get or create profile
            $profile = $user->profile;
            if (!$profile) {
                $profile = new UserProfile();
                $profile->user_id = $user->id;
            }
            
            // Update profile fields
            $profile->fill(array_intersect_key($validated, array_flip([
                'display_name',
                'bio',
                'birthdate',
                'gender',
                'pronouns',
                'sexual_orientation',
                'relationship_style',
                'height_cm',
                'body_type',
                'ethnicity',
                'breast_size',
                'tattoos',
                'piercings',
                'hair_color',
                'eye_color',
                'skin_tone',
                'facial_hair',
                'dominant_hand',
                'fitness_level',
                'clothing_style',
                'penis_length_cm',
                'penis_girth_cm',
                'occupation',
                'education',
                'relationship_status',
                'smoking_status',
                'drinking_status',
                'cannabis_status',
                'dietary_preferences',
                'zodiac_sign',
                'relationship_goals',
                'has_children',
                'wants_children',
                'has_pets',
                'love_language',
                'personality_type',
                'political_views',
                'religion',
                'sleep_schedule',
                'social_media',
                'interests',
                'languages',
                'fetishes',
                'sti_status',
            ])));
            
            // Handle location fields
            if (isset($validated['location'])) {
                $location = $validated['location'];
                if (isset($location['latitude'])) {
                    $profile->latitude = $location['latitude'];
                }
                if (isset($location['longitude'])) {
                    $profile->longitude = $location['longitude'];
                }
                if (isset($location['city'])) {
                    $profile->location_name = $location['city'] . ', ' . ($location['state'] ?? '');
                }
            }

            // Handle travel mode
            if (isset($validated['is_travel_mode'])) {
                $profile->is_travel_mode = $validated['is_travel_mode'];
            }
            
            if (isset($validated['travel_location'])) {
                $travel = $validated['travel_location'];
                if (isset($travel['latitude'])) {
                    $profile->travel_latitude = $travel['latitude'];
                }
                if (isset($travel['longitude'])) {
                    $profile->travel_longitude = $travel['longitude'];
                }
                if (isset($travel['name'])) {
                    $profile->travel_location_name = $travel['name'];
                }
            }
            
            // Handle JSON fields
            if (isset($validated['looking_for'])) {
                $profile->looking_for = $validated['looking_for'];
            }
            
            if (isset($validated['interested_in'])) {
                $profile->interested_in = $validated['interested_in'];
            }
            
            if (isset($validated['preferences'])) {
                $profile->preferences = array_merge(
                    $profile->preferences ?? [],
                    $validated['preferences']
                );
            }
            
            $profile->save();
            
            // Update user's basic info if provided
            if (isset($validated['email'])) {
                $user->email = $validated['email'];
            }
            
            $user->save();
            
            // Reload with fresh data
            $user->load('profile');
            
            Log::info('Profile updated', [
                'user_id' => $user->id,
                'updated_fields' => array_keys($validated),
            ]);
            
            return response()->json([
                'success' => true,
                'message' => 'Profile updated successfully',
                'data' => new UserProfileResource($user),
                'profile_complete' => $this->isProfileComplete($profile),
            ]);
            
        } catch (\Exception $e) {
            Log::error('Profile update error', [
                'user_id' => auth()->id(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            return response()->json([
                'message' => 'Error updating profile',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error',
            ], 500);
        }
    }
    
    /**
     * Check if profile is complete enough for matching
     * 
     * @param UserProfile $profile
     * @return bool
     */
    private function isProfileComplete(UserProfile $profile): bool
    {
        // Must have basic identity and location
        $requiredFields = [
            'display_name',
            'gender',
            'latitude',
            'longitude',
            'looking_for',
        ];

        foreach ($requiredFields as $field) {
            if (empty($profile->$field)) {
                return false;
            }
        }

        // Validate adult age via DOB (>= 18)
        if (empty($profile->birthdate)) {
            return false;
        }
        $dob = $profile->birthdate instanceof \DateTimeInterface
            ? Carbon::instance($profile->birthdate)
            : Carbon::parse($profile->birthdate);
        if ($dob->age < 18) {
            return false;
        }

        // looking_for should be an array with at least one entry
        if (!is_array($profile->looking_for) || count($profile->looking_for) === 0) {
            return false;
        }
        
        return true;
    }
    
    /**
     * Get profile completion percentage
     * 
     * @return JsonResponse
     */
    public function completeness(Request $request): JsonResponse
    {
        try {
            $user = auth()->user();
            $profile = $user->profile;
            
            if (!$profile) {
                return response()->json([
                    'percentage' => 0,
                    'required_complete' => false,
                    'missing_required' => ['display_name', 'birthdate', 'gender', 'location', 'looking_for'],
                    'missing_optional' => ['bio', 'pronouns', 'sexual_orientation', 'relationship_style', 'preferences'],
                    'sections' => [
                        'basic' => false,
                        'location' => false,
                        'preferences' => false,
                        'interests' => false,
                        'physical' => false,
                        'lifestyle' => false,
                    ],
                ]);
            }
            
            // Helper to check nested preferences
            $hasPref = function($key) use ($profile) {
                return !empty($profile->preferences) && !empty($profile->preferences[$key]);
            };

            // Check sections
            $sections = [
                'basic' => !empty($profile->display_name) && !empty($profile->birthdate) && !empty($profile->gender),
                'location' => !empty($profile->latitude) && !empty($profile->longitude),
                'preferences' => !empty($profile->looking_for) && count($profile->looking_for ?? []) > 0,
                'interests' => !empty($profile->interests) || $hasPref('hobbies') || $hasPref('music') || $hasPref('sports'),
                'physical' => !empty($profile->body_type) || $hasPref('body_type'),
                'lifestyle' => !empty($profile->smoking_status) || $hasPref('smoking') || $hasPref('drinking') || $hasPref('exercise'),
            ];

            // Required fields
            $required = ['display_name', 'birthdate', 'gender', 'latitude', 'looking_for'];
            $missingRequired = [];
            foreach ($required as $field) {
                if (empty($profile->$field)) {
                    // Map DB field names to frontend friendly names if needed, or just use field name
                    $missingRequired[] = $field;
                }
            }

            // Optional fields
            $optional = ['bio', 'pronouns', 'sexual_orientation', 'relationship_style'];
            $missingOptional = [];
            foreach ($optional as $field) {
                if (empty($profile->$field)) {
                    $missingOptional[] = $field;
                }
            }
            
            // Check preferences for optional missing
            if (!$sections['interests']) $missingOptional[] = 'interests_and_hobbies';
            if (!$sections['physical']) $missingOptional[] = 'physical_attributes';
            if (!$sections['lifestyle']) $missingOptional[] = 'lifestyle_habits';
            
            // Calculate percentage
            // Basic: 30%, Location: 20%, Preferences: 20%, Bio: 10%, Interests: 10%, Lifestyle: 10%
            $score = 0;
            if ($sections['basic']) $score += 30;
            if ($sections['location']) $score += 20;
            if ($sections['preferences']) $score += 20;
            if (!empty($profile->bio)) $score += 10;
            if ($sections['interests']) $score += 10;
            if ($sections['lifestyle']) $score += 10;

            // Cap at 100
            $percentage = min(100, $score);
            
            return response()->json([
                'percentage' => $percentage,
                'required_complete' => count($missingRequired) === 0,
                'missing_required' => $missingRequired,
                'missing_optional' => $missingOptional,
                'sections' => $sections,
            ]);
            
        } catch (\Exception $e) {
            Log::error('Profile completeness check error', [
                'user_id' => auth()->id(),
                'error' => $e->getMessage(),
            ]);
            
            return response()->json([
                'message' => 'Error checking profile completeness',
            ], 500);
        }
    }
}
