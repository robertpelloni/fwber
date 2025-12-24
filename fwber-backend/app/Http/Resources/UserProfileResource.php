<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Carbon\Carbon;

/**
 * User Profile API Resource
 * 
 * Transforms User + UserProfile data for API responses
 * Used by ProfileController for Next.js frontend
 * 
 * Created: 2025-10-18 (Multi-AI Implementation)
 */
class UserProfileResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        // Safely access profile with fallback to null
        $profile = null;
        try {
            $profile = $this->profile;
        } catch (\Exception $e) {
            // Profile relationship failed to load
        }

        return [
            'id' => $this->id,
            'email' => $this->email,
            'email_verified' => (bool) $this->email_verified_at,
            'created_at' => $this->created_at?->toISOString(),
            'last_online' => $this->updated_at?->toISOString(),
            
            // Profile data
            'profile' => [
                'display_name' => $profile?->display_name,
                'bio' => $profile?->bio,
                // Include birthdate for client-side prefill (Y-m-d)
                'birthdate' => $profile?->birthdate instanceof \DateTimeInterface
                    ? $profile->birthdate->format('Y-m-d')
                    : ($profile?->birthdate
                        ? Carbon::parse($profile->birthdate)->toDateString()
                        : null),
                // Compute age from birthdate when present
                'age' => $profile?->birthdate
                    ? Carbon::parse($profile->birthdate)->age
                    : null,
                'gender' => $profile?->gender,
                'pronouns' => $profile?->pronouns,
                'sexual_orientation' => $profile?->sexual_orientation,
                'relationship_style' => $profile?->relationship_style,
                'looking_for' => $profile?->looking_for ?? [],
                'interested_in' => $profile?->interested_in ?? [],
                'interests' => $profile?->interests ?? [],
                'languages' => $profile?->languages ?? [],
                'fetishes' => $profile?->fetishes ?? [],
                'sti_status' => $profile?->sti_status ?? [],
                'is_incognito' => (bool) $profile?->is_incognito,
                
                // Physical Attributes
                'height_cm' => $profile?->height_cm,
                'body_type' => $profile?->body_type,
                'ethnicity' => $profile?->ethnicity,
                'breast_size' => $profile?->breast_size,
                'tattoos' => $profile?->tattoos,
                'piercings' => $profile?->piercings,
                'hair_color' => $profile?->hair_color,
                'eye_color' => $profile?->eye_color,
                'skin_tone' => $profile?->skin_tone,
                'facial_hair' => $profile?->facial_hair,
                'dominant_hand' => $profile?->dominant_hand,
                'fitness_level' => $profile?->fitness_level,
                'clothing_style' => $profile?->clothing_style,
                'avatar_prompt' => $profile?->avatar_prompt,
                'avatar_status' => $profile?->avatar_status,

                // Intimate Attributes
                'penis_length_cm' => $profile?->penis_length_cm,
                'penis_girth_cm' => $profile?->penis_girth_cm,

                // Lifestyle Attributes
                'occupation' => $profile?->occupation,
                'education' => $profile?->education,
                'relationship_status' => $profile?->relationship_status,
                'smoking_status' => $profile?->smoking_status,
                'drinking_status' => $profile?->drinking_status,
                'cannabis_status' => $profile?->cannabis_status,
                'dietary_preferences' => $profile?->dietary_preferences,
                'zodiac_sign' => $profile?->zodiac_sign,
                'relationship_goals' => $profile?->relationship_goals,
                'has_children' => $profile?->has_children,
                'wants_children' => $profile?->wants_children,
                'has_pets' => $profile?->has_pets,

                // Social & Personality
                'love_language' => $profile?->love_language,
                'personality_type' => $profile?->personality_type,
                'political_views' => $profile?->political_views,
                'religion' => $profile?->religion,
                'sleep_schedule' => $profile?->sleep_schedule,
                'social_media' => $profile?->social_media ?? [],
                
                // Location (with privacy controls)
                'location' => [
                    'latitude' => $profile?->latitude,
                    'longitude' => $profile?->longitude,
                    'location_name' => $profile?->location_name,
                    'city' => $this->extractCityFromLocation($profile),
                    'state' => $this->extractStateFromLocation($profile),
                    'max_distance' => data_get($profile?->preferences, 'max_distance', 25),
                ],
                
                // Preferences
                'preferences' => $profile?->preferences ?? [],
                
                // Photos
                'photos' => $this->when($this->relationLoaded('photos'), function () {
                    return $this->photos->map(function ($photo) {
                        return [
                            'id' => $photo->id,
                            'url' => $photo->url,
                            'is_private' => (bool) $photo->is_private,
                            'is_primary' => (bool) $photo->is_primary,
                            'unlock_price' => $photo->unlock_price,
                            'is_unlocked' => $photo->is_unlocked,
                        ];
                    });
                }),
                
                // Completion status
                'profile_complete' => $this->isProfileComplete($profile),
                'completion_percentage' => $this->getCompletionPercentage($profile),
            ],
        ];
    }
    
    /**
     * Check if profile is complete
     */
    private function isProfileComplete($profile): bool
    {
        try {
            if (!$profile) {
                return false;
            }
            
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

            // Require DOB and adulthood >= 18
            if (empty($profile->birthdate)) {
                return false;
            }
            $dob = $profile->birthdate instanceof \DateTimeInterface
                ? Carbon::instance($profile->birthdate)
                : Carbon::parse($profile->birthdate);
            if ($dob->age < 18) {
                return false;
            }

            return true;
        } catch (\Exception $e) {
            return false;
        }
    }
    
    /**
     * Calculate profile completion percentage
     */
    private function getCompletionPercentage($profile): int
    {
        try {
            if (!$profile) {
                return 0;
            }
            
            $allFields = [
                'display_name',
                'bio',
                'birthdate',
                'gender',
                'pronouns',
                'sexual_orientation',
                'relationship_style',
                'looking_for',
                'latitude',
                'longitude',
                'preferences',
            ];
            
            $completed = 0;
            foreach ($allFields as $field) {
                if (!empty($profile->$field)) {
                    $completed++;
                }
            }
            
            return round(($completed / count($allFields)) * 100);
        } catch (\Exception $e) {
            return 0;
        }
    }
    
    /**
     * Extract city from location description
     */
    private function extractCityFromLocation($profile): ?string
    {
        if (!$profile?->location_name) {
            return null;
        }
        
        $parts = explode(',', $profile->location_name);
        return trim($parts[0]) ?: null;
    }
    
    /**
     * Extract state from location description
     */
    private function extractStateFromLocation($profile): ?string
    {
        if (!$profile?->location_name) {
            return null;
        }
        
        $parts = explode(',', $profile->location_name);
        return isset($parts[1]) ? trim($parts[1]) : null;
    }
}

