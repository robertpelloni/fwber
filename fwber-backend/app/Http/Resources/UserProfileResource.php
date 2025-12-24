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
        return [
            'id' => $this->id,
            'email' => $this->email,
            'email_verified' => (bool) $this->email_verified_at,
            'created_at' => $this->created_at?->toISOString(),
            'last_online' => $this->updated_at?->toISOString(),
            
            // Profile data
            'profile' => [
                'display_name' => $this->profile?->display_name,
                'bio' => $this->profile?->bio,
                // Include birthdate for client-side prefill (Y-m-d)
                'birthdate' => $this->profile?->birthdate instanceof \DateTimeInterface
                    ? $this->profile->birthdate->format('Y-m-d')
                    : ($this->profile?->birthdate
                        ? Carbon::parse($this->profile->birthdate)->toDateString()
                        : null),
                // Compute age from birthdate when present
                'age' => $this->profile?->birthdate
                    ? Carbon::parse($this->profile->birthdate)->age
                    : null,
                'gender' => $this->profile?->gender,
                'pronouns' => $this->profile?->pronouns,
                'sexual_orientation' => $this->profile?->sexual_orientation,
                'relationship_style' => $this->profile?->relationship_style,
                'looking_for' => $this->profile?->looking_for ?? [],
                'interested_in' => $this->profile?->interested_in ?? [],
                'interests' => $this->profile?->interests ?? [],
                'languages' => $this->profile?->languages ?? [],
                'fetishes' => $this->profile?->fetishes ?? [],
                'sti_status' => $this->profile?->sti_status ?? [],
                'is_incognito' => (bool) $this->profile?->is_incognito,
                
                // Physical Attributes
                'height_cm' => $this->profile?->height_cm,
                'body_type' => $this->profile?->body_type,
                'ethnicity' => $this->profile?->ethnicity,
                'breast_size' => $this->profile?->breast_size,
                'tattoos' => $this->profile?->tattoos,
                'piercings' => $this->profile?->piercings,
                'hair_color' => $this->profile?->hair_color,
                'eye_color' => $this->profile?->eye_color,
                'skin_tone' => $this->profile?->skin_tone,
                'facial_hair' => $this->profile?->facial_hair,
                'dominant_hand' => $this->profile?->dominant_hand,
                'fitness_level' => $this->profile?->fitness_level,
                'clothing_style' => $this->profile?->clothing_style,
                'avatar_prompt' => $this->profile?->avatar_prompt,
                'avatar_status' => $this->profile?->avatar_status,

                // Intimate Attributes
                'penis_length_cm' => $this->profile?->penis_length_cm,
                'penis_girth_cm' => $this->profile?->penis_girth_cm,

                // Lifestyle Attributes
                'occupation' => $this->profile?->occupation,
                'education' => $this->profile?->education,
                'relationship_status' => $this->profile?->relationship_status,
                'smoking_status' => $this->profile?->smoking_status,
                'drinking_status' => $this->profile?->drinking_status,
                'cannabis_status' => $this->profile?->cannabis_status,
                'dietary_preferences' => $this->profile?->dietary_preferences,
                'zodiac_sign' => $this->profile?->zodiac_sign,
                'relationship_goals' => $this->profile?->relationship_goals,
                'has_children' => $this->profile?->has_children,
                'wants_children' => $this->profile?->wants_children,
                'has_pets' => $this->profile?->has_pets,

                // Social & Personality
                'love_language' => $this->profile?->love_language,
                'personality_type' => $this->profile?->personality_type,
                'political_views' => $this->profile?->political_views,
                'religion' => $this->profile?->religion,
                'sleep_schedule' => $this->profile?->sleep_schedule,
                'social_media' => $this->profile?->social_media ?? [],
                
                // Location (with privacy controls)
                'location' => [
                    'latitude' => $this->profile?->latitude,
                    'longitude' => $this->profile?->longitude,
                    'location_name' => $this->profile?->location_name,
                    'city' => $this->extractCityFromLocation(),
                    'state' => $this->extractStateFromLocation(),
                    'max_distance' => data_get($this->profile?->preferences, 'max_distance', 25),
                ],
                
                // Preferences
                'preferences' => $this->profile?->preferences ?? [],
                
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
                'profile_complete' => $this->isProfileComplete(),
                'completion_percentage' => $this->getCompletionPercentage(),
            ],
        ];
    }
    
    /**
     * Check if profile is complete
     */
    private function isProfileComplete(): bool
    {
        try {
            if (!$this->profile) {
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
                if (empty($this->profile->$field)) {
                    return false;
                }
            }

            // Require DOB and adulthood >= 18
            if (empty($this->profile->birthdate)) {
                return false;
            }
            $dob = $this->profile->birthdate instanceof \DateTimeInterface
                ? Carbon::instance($this->profile->birthdate)
                : Carbon::parse($this->profile->birthdate);
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
    private function getCompletionPercentage(): int
    {
        try {
            if (!$this->profile) {
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
                if (!empty($this->profile->$field)) {
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
    private function extractCityFromLocation(): ?string
    {
        if (!$this->profile?->location_name) {
            return null;
        }
        
        $parts = explode(',', $this->profile->location_name);
        return trim($parts[0]) ?: null;
    }
    
    /**
     * Extract state from location description
     */
    private function extractStateFromLocation(): ?string
    {
        if (!$this->profile?->location_name) {
            return null;
        }
        
        $parts = explode(',', $this->profile->location_name);
        return isset($parts[1]) ? trim($parts[1]) : null;
    }
}

