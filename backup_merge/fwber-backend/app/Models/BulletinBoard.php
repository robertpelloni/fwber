<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class BulletinBoard extends Model
{
    use HasFactory;

    protected $fillable = [
        'geohash',
        'center_lat',
        'center_lng',
        'radius_meters',
        'name',
        'description',
        'is_active',
        'message_count',
        'active_users',
        'last_activity_at',
    ];

    protected $casts = [
        'center_lat' => 'decimal:8',
        'center_lng' => 'decimal:8',
        'is_active' => 'boolean',
        'last_activity_at' => 'datetime',
    ];

    /**
     * Get all messages for this bulletin board
     */
    public function messages(): HasMany
    {
        return $this->hasMany(BulletinMessage::class)->orderBy('created_at', 'desc');
    }

    /**
     * Get recent messages (last 50)
     */
    public function recentMessages(): HasMany
    {
        return $this->hasMany(BulletinMessage::class)
            ->where('is_moderated', false)
            ->orderBy('created_at', 'desc')
            ->limit(50);
    }

    /**
     * Get active users for this board
     */
    public function activeUsers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'bulletin_board_users')
            ->withPivot(['last_seen_at', 'is_online'])
            ->withTimestamps();
    }

    /**
     * Calculate distance from a point to this board's center
     */
    public function distanceFrom(float $lat, float $lng): float
    {
        $earthRadius = 6371000; // Earth's radius in meters
        
        $lat1 = deg2rad($this->center_lat);
        $lng1 = deg2rad($this->center_lng);
        $lat2 = deg2rad($lat);
        $lng2 = deg2rad($lng);
        
        $dlat = $lat2 - $lat1;
        $dlng = $lng2 - $lng1;
        
        $a = sin($dlat / 2) * sin($dlat / 2) +
             cos($lat1) * cos($lat2) *
             sin($dlng / 2) * sin($dlng / 2);
        
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
        
        return $earthRadius * $c;
    }

    /**
     * Check if a point is within this board's radius
     */
    public function containsPoint(float $lat, float $lng): bool
    {
        return $this->distanceFrom($lat, $lng) <= $this->radius_meters;
    }

    /**
     * Update activity statistics
     */
    public function updateActivity(): void
    {
        $this->update([
            'message_count' => $this->messages()->count(),
            'active_users' => $this->activeUsers()->wherePivot('is_online', true)->count(),
            'last_activity_at' => now(),
        ]);
    }

    /**
     * Scope for active boards
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for boards near a location
     */
    public function scopeNearLocation($query, float $lat, float $lng, int $radiusMeters = 5000)
    {
        if (config('database.default') === 'sqlite' || \Illuminate\Support\Facades\DB::connection()->getDriverName() === 'sqlite') {
             // Simple box approximation for testing
             // 1 degree latitude ~= 111km = 111000m
             $latRange = $radiusMeters / 111000;
             // 1 degree longitude ~= 111km * cos(lat)
             // Avoid division by zero if lat is 90/-90
             $cosLat = cos(deg2rad($lat));
             $lonRange = $cosLat == 0 ? 180 : $radiusMeters / (111000 * abs($cosLat));
             
             return $query->whereBetween('center_lat', [$lat - $latRange, $lat + $latRange])
                          ->whereBetween('center_lng', [$lng - $lonRange, $lng + $lonRange]);
        }

        return $query->whereRaw(
            "ST_Distance_Sphere(
                POINT(center_lng, center_lat), 
                POINT(?, ?)
            ) <= ?",
            [$lng, $lat, $radiusMeters]
        );
    }
}
