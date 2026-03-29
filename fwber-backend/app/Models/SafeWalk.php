<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $user_id
 * @property string $status
 * @property string|null $destination
 * @property float|null $start_lat
 * @property float|null $start_lng
 * @property float|null $current_lat
 * @property float|null $current_lng
 * @property float|null $dest_lat
 * @property float|null $dest_lng
 * @property \Illuminate\Support\Carbon|null $started_at
 * @property \Illuminate\Support\Carbon|null $ended_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\User $user
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SafeWalk active()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SafeWalk newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SafeWalk newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SafeWalk query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SafeWalk whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SafeWalk whereCurrentLat($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SafeWalk whereCurrentLng($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SafeWalk whereDestLat($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SafeWalk whereDestLng($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SafeWalk whereDestination($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SafeWalk whereEndedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SafeWalk whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SafeWalk whereStartLat($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SafeWalk whereStartLng($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SafeWalk whereStartedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SafeWalk whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SafeWalk whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SafeWalk whereUserId($value)
 *
 * @mixin \Eloquent
 */
class SafeWalk extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'status', 'destination',
        'start_lat', 'start_lng',
        'current_lat', 'current_lng',
        'dest_lat', 'dest_lng',
        'started_at', 'ended_at',
    ];

    protected $casts = [
        'start_lat' => 'float', 'start_lng' => 'float',
        'current_lat' => 'float', 'current_lng' => 'float',
        'dest_lat' => 'float', 'dest_lng' => 'float',
        'started_at' => 'datetime', 'ended_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }
}
