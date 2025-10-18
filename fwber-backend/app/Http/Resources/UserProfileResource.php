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
                'looking_for' => $this->profile?->looking_for ?? [],
                
                // Location (with privacy controls)
                'location' => [
                    'latitude' => $this->profile?->latitude,
                    'longitude' => $this->profile?->longitude,
                    'max_distance' => $this->profile?->max_distance ?? 25,
                    'city' => $this->profile?->city,
                    'state' => $this->profile?->state,
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
            'latitude',
            'longitude',
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
            'looking_for',
            'latitude',
            'longitude',
            'max_distance',
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
}

