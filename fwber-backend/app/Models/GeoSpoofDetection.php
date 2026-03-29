<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $user_id
 * @property string|null $ip_address
 * @property numeric $latitude
 * @property numeric $longitude
 * @property numeric|null $ip_latitude
 * @property numeric|null $ip_longitude
 * @property int|null $distance_km
 * @property int|null $velocity_kmh
 * @property int $suspicion_score
 * @property array<array-key, mixed>|null $detection_flags
 * @property bool $is_confirmed_spoof
 * @property \Illuminate\Support\Carbon $detected_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\User $user
 * @method static \Database\Factories\GeoSpoofDetectionFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GeoSpoofDetection highRisk()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GeoSpoofDetection newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GeoSpoofDetection newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GeoSpoofDetection query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GeoSpoofDetection unconfirmed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GeoSpoofDetection whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GeoSpoofDetection whereDetectedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GeoSpoofDetection whereDetectionFlags($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GeoSpoofDetection whereDistanceKm($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GeoSpoofDetection whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GeoSpoofDetection whereIpAddress($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GeoSpoofDetection whereIpLatitude($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GeoSpoofDetection whereIpLongitude($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GeoSpoofDetection whereIsConfirmedSpoof($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GeoSpoofDetection whereLatitude($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GeoSpoofDetection whereLongitude($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GeoSpoofDetection whereSuspicionScore($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GeoSpoofDetection whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GeoSpoofDetection whereUserId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GeoSpoofDetection whereVelocityKmh($value)
 * @mixin \Eloquent
 */
class GeoSpoofDetection extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'ip_address',
        'latitude',
        'longitude',
        'ip_latitude',
        'ip_longitude',
        'distance_km',
        'velocity_kmh',
        'suspicion_score',
        'detection_flags',
        'is_confirmed_spoof',
        'detected_at',
    ];

    protected $casts = [
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
        'ip_latitude' => 'decimal:8',
        'ip_longitude' => 'decimal:8',
        'distance_km' => 'integer',
        'velocity_kmh' => 'integer',
        'suspicion_score' => 'integer',
        'detection_flags' => 'array',
        'is_confirmed_spoof' => 'boolean',
        'detected_at' => 'datetime',
    ];

    /**
     * Get the user associated with this detection.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Determine if this detection is high risk.
     */
    public function isHighRisk(): bool
    {
        return $this->suspicion_score >= 70;
    }

    /**
     * Scope to get high risk detections.
     */
    public function scopeHighRisk($query)
    {
        return $query->where('suspicion_score', '>=', 70);
    }

    /**
     * Scope to get unconfirmed detections.
     */
    public function scopeUnconfirmed($query)
    {
        return $query->where('is_confirmed_spoof', false);
    }
}
