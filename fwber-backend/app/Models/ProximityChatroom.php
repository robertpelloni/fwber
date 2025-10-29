<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Facades\DB;

class ProximityChatroom extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'type',
        'venue_name',
        'venue_type',
        'event_name',
        'event_date',
        'event_start_time',
        'event_end_time',
        'latitude',
        'longitude',
        'radius_meters',
        'geohash',
        'city',
        'neighborhood',
        'address',
        'tags',
        'settings',
        'created_by',
        'is_active',
        'is_public',
        'requires_approval',
        'max_members',
        'current_members',
        'message_count',
        'last_activity_at',
        'expires_at',
    ];

    protected $casts = [
        'tags' => 'array',
        'settings' => 'array',
        'is_active' => 'boolean',
        'is_public' => 'boolean',
        'requires_approval' => 'boolean',
        'current_members' => 'integer',
        'message_count' => 'integer',
        'last_activity_at' => 'datetime',
        'expires_at' => 'datetime',
        'event_date' => 'date',
        'event_start_time' => 'datetime:H:i',
        'event_end_time' => 'datetime:H:i',
    ];

    /**
     * Get the user who created the proximity chatroom
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get all members of the proximity chatroom
     */
    public function members(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'proximity_chatroom_members')
            ->withPivot([
                'role', 'is_muted', 'is_banned', 'latitude', 'longitude', 
                'distance_meters', 'joined_at', 'last_seen_at', 'last_location_update',
                'preferences', 'professional_info', 'interests', 'is_visible',
                'is_networking', 'is_social'
            ])
            ->withTimestamps();
    }

    /**
     * Get all messages in the proximity chatroom
     */
    public function messages(): HasMany
    {
        return $this->hasMany(ProximityChatroomMessage::class);
    }

    /**
     * Get active members (not banned)
     */
    public function activeMembers(): BelongsToMany
    {
        return $this->members()->wherePivot('is_banned', false);
    }

    /**
     * Get visible members (want to be discoverable)
     */
    public function visibleMembers(): BelongsToMany
    {
        return $this->activeMembers()->wherePivot('is_visible', true);
    }

    /**
     * Get networking members (looking for professional connections)
     */
    public function networkingMembers(): BelongsToMany
    {
        return $this->activeMembers()->wherePivot('is_networking', true);
    }

    /**
     * Get social members (looking for social connections)
     */
    public function socialMembers(): BelongsToMany
    {
        return $this->activeMembers()->wherePivot('is_social', true);
    }

    /**
     * Get moderators and admins
     */
    public function moderators(): BelongsToMany
    {
        return $this->members()->wherePivotIn('role', ['moderator', 'admin']);
    }

    /**
     * Check if user is a member of this proximity chatroom
     */
    public function hasMember(User $user): bool
    {
        return $this->members()->where('user_id', $user->id)->exists();
    }

    /**
     * Check if user is a moderator or admin
     */
    public function hasModerator(User $user): bool
    {
        return $this->members()
            ->where('user_id', $user->id)
            ->wherePivotIn('role', ['moderator', 'admin'])
            ->exists();
    }

    /**
     * Check if user is banned
     */
    public function isBanned(User $user): bool
    {
        return $this->members()
            ->where('user_id', $user->id)
            ->wherePivot('is_banned', true)
            ->exists();
    }

    /**
     * Add a member to the proximity chatroom
     */
    public function addMember(User $user, array $location = [], array $preferences = []): void
    {
        $this->members()->attach($user->id, array_merge([
            'role' => 'member',
            'joined_at' => now(),
            'last_seen_at' => now(),
            'last_location_update' => now(),
            'is_visible' => true,
            'is_networking' => false,
            'is_social' => true,
        ], $location, $preferences));

        $this->increment('current_members');
    }

    /**
     * Remove a member from the proximity chatroom
     */
    public function removeMember(User $user): void
    {
        $this->members()->detach($user->id);
        $this->decrement('current_members');
    }

    /**
     * Update member location
     */
    public function updateMemberLocation(User $user, float $latitude, float $longitude): void
    {
        $distance = $this->calculateDistance($latitude, $longitude);
        
        $this->members()->updateExistingPivot($user->id, [
            'latitude' => $latitude,
            'longitude' => $longitude,
            'distance_meters' => $distance,
            'last_location_update' => now(),
        ]);
    }

    /**
     * Calculate distance from chatroom center
     */
    public function calculateDistance(float $latitude, float $longitude): int
    {
        $earthRadius = 6371000; // Earth's radius in meters
        
        $lat1 = deg2rad($this->latitude);
        $lon1 = deg2rad($this->longitude);
        $lat2 = deg2rad($latitude);
        $lon2 = deg2rad($longitude);
        
        $dlat = $lat2 - $lat1;
        $dlon = $lon2 - $lon1;
        
        $a = sin($dlat/2) * sin($dlat/2) + cos($lat1) * cos($lat2) * sin($dlon/2) * sin($dlon/2);
        $c = 2 * atan2(sqrt($a), sqrt(1-$a));
        
        return round($earthRadius * $c);
    }

    /**
     * Find nearby proximity chatrooms
     */
    public static function findNearby(float $latitude, float $longitude, int $radiusMeters = 1000): \Illuminate\Database\Eloquent\Collection
    {
        $earthRadius = 6371000; // Earth's radius in meters
        
        return static::select('*')
            ->selectRaw("({$earthRadius} * acos(cos(radians(?)) * cos(radians(latitude)) * cos(radians(longitude) - radians(?)) + sin(radians(?)) * sin(radians(latitude)))) AS distance", [
                $latitude, $longitude, $latitude
            ])
            ->where('is_active', true)
            ->where('is_public', true)
            ->having('distance', '<=', $radiusMeters)
            ->orderBy('distance')
            ->get();
    }

    /**
     * Check if user is within proximity
     */
    public function isWithinProximity(float $latitude, float $longitude): bool
    {
        $distance = $this->calculateDistance($latitude, $longitude);
        return $distance <= $this->radius_meters;
    }

    /**
     * Update last activity timestamp
     */
    public function updateActivity(): void
    {
        $this->update(['last_activity_at' => now()]);
    }

    /**
     * Get proximity chatroom URL
     */
    public function getUrlAttribute(): string
    {
        return "/proximity-chatrooms/{$this->id}";
    }

    /**
     * Get display name with type prefix
     */
    public function getDisplayNameAttribute(): string
    {
        $prefixes = [
            'conference' => '🎤',
            'event' => '🎉',
            'venue' => '🏢',
            'area' => '📍',
            'temporary' => '⏰',
        ];

        $prefix = $prefixes[$this->type] ?? '📍';
        return "{$prefix} {$this->name}";
    }

    /**
     * Check if chatroom is expired
     */
    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    /**
     * Scope for active proximity chatrooms
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('expires_at')
                  ->orWhere('expires_at', '>', now());
            });
    }

    /**
     * Scope for public proximity chatrooms
     */
    public function scopePublic($query)
    {
        return $query->where('is_public', true);
    }

    /**
     * Scope for proximity chatrooms by type
     */
    public function scopeByType($query, string $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Scope for proximity chatrooms by venue type
     */
    public function scopeByVenueType($query, string $venueType)
    {
        return $query->where('venue_type', $venueType);
    }

    /**
     * Scope for proximity chatrooms by city
     */
    public function scopeByCity($query, string $city)
    {
        return $query->where('city', $city);
    }

    /**
     * Scope for proximity chatrooms within radius
     */
    public function scopeWithinRadius($query, float $latitude, float $longitude, int $radiusMeters)
    {
        $earthRadius = 6371000;
        
        return $query->selectRaw("({$earthRadius} * acos(cos(radians(?)) * cos(radians(latitude)) * cos(radians(longitude) - radians(?)) + sin(radians(?)) * sin(radians(latitude)))) AS distance", [
            $latitude, $longitude, $latitude
        ])
        ->having('distance', '<=', $radiusMeters)
        ->orderBy('distance');
    }
}
