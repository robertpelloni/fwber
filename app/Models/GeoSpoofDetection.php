<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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
