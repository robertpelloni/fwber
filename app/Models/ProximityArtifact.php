<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Builder;

class ProximityArtifact extends Model
{
    use HasFactory, SoftDeletes;

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
        return $this->fuzzCoord((float)$this->location_lat, 0.0008);
    }

    public function getFuzzedLongitudeAttribute(): float
    {
        return $this->fuzzCoord((float)$this->location_lng, 0.0008);
    }

    private function fuzzCoord(float $value, float $delta): float
    {
        // Deterministic small jitter based on id for stability across requests
        $seed = crc32((string)($this->id ?? 0));
        mt_srand($seed);
        $offset = (mt_rand() / mt_getrandmax()) * (2 * $delta) - $delta;
        return round($value + $offset, 7);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
