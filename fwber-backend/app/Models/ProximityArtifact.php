<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property int $user_id
 * @property string $type
 * @property float $latitude
 * @property float $longitude
 * @property string $geohash
 * @property array|null $metadata
 * @property bool $is_flagged
 * @property string|null $flag_reason
 */
class ProximityArtifact extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'type',
        'content',
        'latitude',
        'longitude',
        'geohash',
        'visibility_radius_m',
        'expires_at',
        'metadata',
        'is_flagged',
        'flag_reason',
    ];

    protected $casts = [
        'latitude' => 'float',
        'longitude' => 'float',
        'metadata' => 'array',
        'is_flagged' => 'boolean',
        'visibility_radius_m' => 'integer',
        'expires_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
