<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Prunable;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property int|null $user_id
 * @property string $type
 * @property string $content
 * @property numeric $location_lat
 * @property numeric $location_lng
 * @property int $visibility_radius_m
 * @property string $moderation_status
 * @property array<array-key, mixed>|null $meta
 * @property \Illuminate\Support\Carbon $expires_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property bool $is_flagged
 * @property int $flag_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ProximityArtifactComment> $comments
 * @property-read int|null $comments_count
 * @property-read float $fuzzed_latitude
 * @property-read float $fuzzed_longitude
 * @property-read \App\Models\User|null $user
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ProximityArtifactVote> $votes
 * @property-read int|null $votes_count
 * @method static Builder<static>|ProximityArtifact active()
 * @method static \Database\Factories\ProximityArtifactFactory factory($count = null, $state = [])
 * @method static Builder<static>|ProximityArtifact newModelQuery()
 * @method static Builder<static>|ProximityArtifact newQuery()
 * @method static Builder<static>|ProximityArtifact onlyTrashed()
 * @method static Builder<static>|ProximityArtifact query()
 * @method static Builder<static>|ProximityArtifact type(string $type)
 * @method static Builder<static>|ProximityArtifact whereContent($value)
 * @method static Builder<static>|ProximityArtifact whereCreatedAt($value)
 * @method static Builder<static>|ProximityArtifact whereDeletedAt($value)
 * @method static Builder<static>|ProximityArtifact whereExpiresAt($value)
 * @method static Builder<static>|ProximityArtifact whereFlagCount($value)
 * @method static Builder<static>|ProximityArtifact whereId($value)
 * @method static Builder<static>|ProximityArtifact whereIsFlagged($value)
 * @method static Builder<static>|ProximityArtifact whereLocationLat($value)
 * @method static Builder<static>|ProximityArtifact whereLocationLng($value)
 * @method static Builder<static>|ProximityArtifact whereMeta($value)
 * @method static Builder<static>|ProximityArtifact whereModerationStatus($value)
 * @method static Builder<static>|ProximityArtifact whereType($value)
 * @method static Builder<static>|ProximityArtifact whereUpdatedAt($value)
 * @method static Builder<static>|ProximityArtifact whereUserId($value)
 * @method static Builder<static>|ProximityArtifact whereVisibilityRadiusM($value)
 * @method static Builder<static>|ProximityArtifact withTrashed(bool $withTrashed = true)
 * @method static Builder<static>|ProximityArtifact withinBox(float $lat, float $lng, float $radiusMeters)
 * @method static Builder<static>|ProximityArtifact withoutTrashed()
 * @mixin \Eloquent
 */
class ProximityArtifact extends Model
{
    use HasFactory, Prunable, SoftDeletes;

    protected $fillable = [
        'user_id', 'type', 'content', 'location_lat', 'location_lng',
        'visibility_radius_m', 'moderation_status', 'meta', 'expires_at',
        'is_flagged', 'flag_count',
    ];

    protected $casts = [
        'meta' => 'array',
        'expires_at' => 'datetime',
        'is_flagged' => 'boolean',
        'flag_count' => 'integer',
    ];

    protected $appends = ['fuzzed_latitude', 'fuzzed_longitude'];

    /**
     * Get the prunable model query.
     */
    public function prunable(): Builder
    {
        return static::where('expires_at', '<=', now());
    }

    public function scopeActive(Builder $q): Builder
    {
        return $q->where('moderation_status', '!=', 'removed')
            ->where('expires_at', '>', now());
    }

    public function scopeType(Builder $q, string $type): Builder
    {
        return $q->where('type', $type);
    }

    public function scopeWithinBox(Builder $q, float $lat, float $lng, float $radiusMeters): Builder
    {
        // Approximate: 1 deg lat ~ 111,000 m; 1 deg lng ~ 111,000 m * cos(lat)
        $latOffset = $radiusMeters / 111000.0;
        $lngOffset = $radiusMeters / (111000.0 * max(0.1, cos(deg2rad($lat))));

        return $q->whereBetween('location_lat', [$lat - $latOffset, $lat + $latOffset])
            ->whereBetween('location_lng', [$lng - $lngOffset, $lng + $lngOffset]);
    }

    public function getFuzzedLatitudeAttribute(): float
    {
        return $this->fuzzCoord((float) $this->location_lat, 0.0008);
    }

    public function getFuzzedLongitudeAttribute(): float
    {
        return $this->fuzzCoord((float) $this->location_lng, 0.0008);
    }

    private function fuzzCoord(float $value, float $delta): float
    {
        // Deterministic small jitter based on id for stability across requests
        $seed = crc32((string) ($this->id ?? 0));
        mt_srand($seed);
        $offset = (mt_rand() / mt_getrandmax()) * (2 * $delta) - $delta;

        return round($value + $offset, 7);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function comments()
    {
        return $this->hasMany(ProximityArtifactComment::class);
    }

    public function votes()
    {
        return $this->hasMany(ProximityArtifactVote::class);
    }
}
