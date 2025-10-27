<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

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
                'age' => $this->profile?->age,
                'gender' => $this->profile?->gender,
                'pronouns' => $this->profile?->pronouns,
                'sexual_orientation' => $this->profile?->sexual_orientation,
                'relationship_style' => $this->profile?->relationship_style,
                'looking_for' => $this->profile?->looking_for ?? [],
                
                // Location (with privacy controls)
                'location' => [
                    'latitude' => $this->profile?->location_latitude,
                    'longitude' => $this->profile?->location_longitude,
                    'max_distance' => $this->profile?->preferences['max_distance'] ?? 25,
                    'city' => $this->extractCityFromLocation(),
                    'state' => $this->extractStateFromLocation(),
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
        if (!$this->profile) {
            return false;
        }
        
        $requiredFields = [
            'display_name',
            'age',
            'gender',
            'location_latitude',
            'location_longitude',
            'looking_for',
        ];
        
        foreach ($requiredFields as $field) {
            if (empty($this->profile->$field)) {
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * Calculate profile completion percentage
     */
    private function getCompletionPercentage(): int
    {
        if (!$this->profile) {
            return 0;
        }
        
        $allFields = [
            'display_name',
            'bio',
            'age',
            'gender',
            'pronouns',
            'sexual_orientation',
            'relationship_style',
            'looking_for',
            'location_latitude',
            'location_longitude',
            'preferences',
        ];
        
        $completed = 0;
        foreach ($allFields as $field) {
            if (!empty($this->profile->$field)) {
                $completed++;
            }
        }
        
        return round(($completed / count($allFields)) * 100);
    }
    
    /**
     * Extract city from location description
     */
    private function extractCityFromLocation(): ?string
    {
        if (!$this->profile?->location_description) {
            return null;
        }
        
        $parts = explode(',', $this->profile->location_description);
        return trim($parts[0]) ?: null;
    }
    
    /**
     * Extract state from location description
     */
    private function extractStateFromLocation(): ?string
    {
        if (!$this->profile?->location_description) {
            return null;
        }
        
        $parts = explode(',', $this->profile->location_description);
        return isset($parts[1]) ? trim($parts[1]) : null;
    }
}

