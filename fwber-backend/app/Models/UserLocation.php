<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * UserLocation Model - Real-time Location Tracking
 * 
 * AI Model: Claude 4.5 - Multi-AI Orchestration
 * Phase: Location-Based Social Features Implementation
 * Purpose: Track user locations for proximity-based discovery
 * 
 * Created: 2025-01-19
 */
class UserLocation extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'latitude',
        'longitude',
        'accuracy',
        'heading',
        'speed',
        'altitude',
        'is_active',
        'privacy_level',
        'last_updated',
    ];

    protected $casts = [
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
        'accuracy' => 'decimal:2',
        'heading' => 'decimal:2',
        'speed' => 'decimal:2',
        'altitude' => 'decimal:2',
        'is_active' => 'boolean',
        'last_updated' => 'datetime',
    ];

    /**
     * Get the user that owns the location
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope to get active locations only
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to get locations by privacy level
     */
    public function scopeByPrivacyLevel($query, $level)
    {
        return $query->where('privacy_level', $level);
    }

    /**
     * Scope to get locations within a radius (in meters)
     */
    public function scopeWithinRadius($query, $latitude, $longitude, $radiusMeters)
    {
        // SQLite support for testing
        if ($query->getConnection()->getDriverName() === 'sqlite') {
            $latDelta = $radiusMeters / 111000;
            $lonDelta = $radiusMeters / (111000 * cos(deg2rad($latitude)));
            
            return $query->whereBetween('latitude', [$latitude - $latDelta, $latitude + $latDelta])
                         ->whereBetween('longitude', [$longitude - $lonDelta, $longitude + $lonDelta]);
        }

        // Using Haversine formula for distance calculation
        $earthRadius = 6371000; // Earth's radius in meters
        
        return $query->selectRaw("
            *,
            (
                {$earthRadius} * acos(
                    cos(radians(?)) * 
                    cos(radians(latitude)) * 
                    cos(radians(longitude) - radians(?)) + 
                    sin(radians(?)) * 
                    sin(radians(latitude))
                )
            ) AS distance
        ", [$latitude, $longitude, $latitude])
        ->having('distance', '<=', $radiusMeters)
        ->orderBy('distance');
    }

    /**
     * Calculate distance to another location in meters
     */
    public function distanceTo($latitude, $longitude): float
    {
        $earthRadius = 6371000; // Earth's radius in meters
        
        $lat1 = deg2rad($this->latitude);
        $lon1 = deg2rad($this->longitude);
        $lat2 = deg2rad($latitude);
        $lon2 = deg2rad($longitude);
        
        $dlat = $lat2 - $lat1;
        $dlon = $lon2 - $lon1;
        
        $a = sin($dlat / 2) * sin($dlat / 2) +
             cos($lat1) * cos($lat2) *
             sin($dlon / 2) * sin($dlon / 2);
        
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
        
        return $earthRadius * $c;
    }

    /**
     * Check if location is recent (within last 5 minutes)
     */
    public function isRecent(): bool
    {
        return $this->last_updated->diffInMinutes(now()) <= 5;
    }

    /**
     * Get formatted distance string
     */
    public function getFormattedDistanceAttribute(): string
    {
        if (!isset($this->distance)) {
            return 'Unknown';
        }
        
        $distance = $this->distance;
        
        if ($distance < 1000) {
            return round($distance) . 'm';
        } else {
            return round($distance / 1000, 1) . 'km';
        }
    }

    /**
     * Get privacy level display name
     */
    public function getPrivacyLevelDisplayAttribute(): string
    {
        return match($this->privacy_level) {
            'public' => 'Public',
            'friends' => 'Friends Only',
            'private' => 'Private',
            default => 'Unknown'
        };
    }
}
