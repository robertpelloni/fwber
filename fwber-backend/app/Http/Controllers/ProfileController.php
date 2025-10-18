<?php

namespace App\Http\Controllers;

use App\Http\Resources\UserProfileResource;
use App\Models\User;
use App\Models\UserProfile;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

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
                'age' => 'sometimes|integer|min:18|max:99',
                'gender' => 'sometimes|string|in:male,female,non-binary,mtf,ftm',
                'looking_for' => 'sometimes|array',
                'looking_for.*' => 'string|in:male,female,non-binary,couple,group',
                'latitude' => 'sometimes|numeric|between:-90,90',
                'longitude' => 'sometimes|numeric|between:-180,180',
                'max_distance' => 'sometimes|integer|min:1|max:500',
                'preferences' => 'sometimes|array',
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
                'age',
                'gender',
                'latitude',
                'longitude',
                'max_distance',
            ]));
            
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
        $requiredFields = [
            'display_name',
            'age',
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
                'looking_for' => 'Looking For',
                'latitude' => 'Location',
                'longitude' => 'Location',
                'max_distance' => 'Maximum Distance',
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
