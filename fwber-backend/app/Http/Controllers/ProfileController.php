<?php

namespace App\Http\Controllers;

use App\Http\Resources\UserProfileResource;
use App\Models\User;
use App\Models\UserProfile;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
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
     * Update authenticated user's profile
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function update(Request $request): JsonResponse
    {
        try {
            $user = auth()->user();
            
            if (!$user) {
                return response()->json(['message' => 'Unauthenticated'], 401);
            }
            
            // Validation rules
            $validator = Validator::make($request->all(), [
                'display_name' => 'sometimes|string|max:50',
                'bio' => 'sometimes|string|max:500',
                // Accept DOB and compute age downstream
                'date_of_birth' => 'sometimes|date|before_or_equal:' . now()->subYears(18)->toDateString() . '|after:1900-01-01',
                'gender' => 'sometimes|string|in:male,female,non-binary,mtf,ftm,other,prefer-not-to-say',
                'pronouns' => 'sometimes|string|in:he/him,she/her,they/them,he/they,she/they,other,prefer-not-to-say',
                'sexual_orientation' => 'sometimes|string|in:straight,gay,lesbian,bisexual,pansexual,asexual,demisexual,queer,questioning,other,prefer-not-to-say',
                'relationship_style' => 'sometimes|string|in:monogamous,non-monogamous,polyamorous,open,swinger,other,prefer-not-to-say',
                'looking_for' => 'sometimes|array',
                'looking_for.*' => 'string|in:friendship,dating,relationship,casual,marriage,networking',
                'location.latitude' => 'sometimes|numeric|between:-90,90',
                'location.longitude' => 'sometimes|numeric|between:-180,180',
                'location.max_distance' => 'sometimes|integer|min:1|max:500',
                'location.city' => 'sometimes|string|max:100',
                'location.state' => 'sometimes|string|max:100',
                'preferences' => 'sometimes|array',
                'preferences.smoking' => 'sometimes|string|in:non-smoker,occasional,regular,social,trying-to-quit',
                'preferences.drinking' => 'sometimes|string|in:non-drinker,occasional,regular,social,sober',
                'preferences.exercise' => 'sometimes|string|in:daily,several-times-week,weekly,occasional,rarely,never',
                'preferences.diet' => 'sometimes|string|in:omnivore,vegetarian,vegan,pescatarian,keto,paleo,gluten-free,other',
                'preferences.pets' => 'sometimes|string|in:love-pets,have-pets,allergic,prefer-no-pets,neutral',
                'preferences.children' => 'sometimes|string|in:have-children,want-children,dont-want-children,maybe-someday,not-sure',
                'preferences.education' => 'sometimes|string|in:high-school,some-college,associates,bachelors,masters,phd,other',
                'preferences.age_range_min' => 'sometimes|integer|min:18|max:99',
                'preferences.age_range_max' => 'sometimes|integer|min:18|max:99',
                'preferences.body_type' => 'sometimes|string|in:slim,athletic,average,curvy,plus-size,muscular',
                'preferences.religion' => 'sometimes|string|in:christian,catholic,jewish,muslim,hindu,buddhist,agnostic,atheist,spiritual,other',
                'preferences.politics' => 'sometimes|string|in:liberal,moderate,conservative,apolitical,other',
                'preferences.hobbies' => 'sometimes|array',
                'preferences.music' => 'sometimes|array',
                'preferences.sports' => 'sometimes|array',
                'preferences.communication_style' => 'sometimes|string|in:direct,diplomatic,humorous,serious,casual,formal',
                'preferences.response_time' => 'sometimes|string|in:immediate,within-hour,within-day,when-convenient,no-rush',
                'preferences.meeting_preference' => 'sometimes|string|in:public-places,coffee-dates,dinner-dates,outdoor-activities,virtual-first,flexible',
            ]);
            
            if ($validator->fails()) {
                return response()->json([
                    'message' => 'Validation failed',
                    'errors' => $validator->errors(),
                ], 422);
            }
            
            // Get or create profile
            $profile = $user->profile;
            if (!$profile) {
                $profile = new UserProfile();
                $profile->user_id = $user->id;
            }
            
            // Update profile fields
            $profile->fill($request->only([
                'display_name',
                'bio',
                'date_of_birth',
                'gender',
                'pronouns',
                'sexual_orientation',
                'relationship_style',
            ]));
            
            // Handle location fields
            if ($request->has('location')) {
                $location = $request->location;
                if (isset($location['latitude'])) {
                    $profile->location_latitude = $location['latitude'];
                }
                if (isset($location['longitude'])) {
                    $profile->location_longitude = $location['longitude'];
                }
                if (isset($location['city'])) {
                    $profile->location_description = $location['city'] . ', ' . ($location['state'] ?? '');
                }
            }
            
            // Handle JSON fields
            if ($request->has('looking_for')) {
                $profile->looking_for = $request->looking_for;
            }
            
            if ($request->has('preferences')) {
                $profile->preferences = array_merge(
                    $profile->preferences ?? [],
                    $request->preferences
                );
            }
            
            $profile->save();
            
            // Update user's basic info if provided
            if ($request->has('email')) {
                $user->email = $request->email;
            }
            
            $user->save();
            
            // Reload with fresh data
            $user->load('profile');
            
            Log::info('Profile updated', [
                'user_id' => $user->id,
                'updated_fields' => array_keys($request->all()),
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
            'location_latitude',
            'location_longitude',
            'looking_for',
        ];

        foreach ($requiredFields as $field) {
            if (empty($profile->$field)) {
                return false;
            }
        }

        // Validate adult age via DOB (>= 18)
        if (empty($profile->date_of_birth)) {
            return false;
        }
        $dob = $profile->date_of_birth instanceof \DateTimeInterface
            ? Carbon::instance($profile->date_of_birth)
            : Carbon::parse($profile->date_of_birth);
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
                    'missing_fields' => ['All profile fields missing'],
                    'is_complete' => false,
                ]);
            }
            
            $allFields = [
                'display_name' => 'Display Name',
                'bio' => 'Bio',
                'age' => 'Age',
                'gender' => 'Gender',
                'pronouns' => 'Pronouns',
                'sexual_orientation' => 'Sexual Orientation',
                'relationship_style' => 'Relationship Style',
                'looking_for' => 'Looking For',
                'location_latitude' => 'Location',
                'location_longitude' => 'Location',
                'preferences' => 'Preferences',
            ];
            
            $completed = 0;
            $missing = [];
            
            foreach ($allFields as $field => $label) {
                if (!empty($profile->$field)) {
                    $completed++;
                } else {
                    if (!in_array($label, $missing)) {
                        $missing[] = $label;
                    }
                }
            }
            
            $percentage = round(($completed / count($allFields)) * 100);
            
            return response()->json([
                'percentage' => $percentage,
                'completed_fields' => $completed,
                'total_fields' => count($allFields),
                'missing_fields' => $missing,
                'is_complete' => $this->isProfileComplete($profile),
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
