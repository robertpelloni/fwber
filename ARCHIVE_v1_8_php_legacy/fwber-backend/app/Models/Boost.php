<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $user_id
 * @property \Illuminate\Support\Carbon|null $started_at
 * @property \Illuminate\Support\Carbon|null $expires_at
 * @property string $boost_type
 * @property string $status
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\User $user
 *
 * @method static Builder<static>|Boost active()
 * @method static Builder<static>|Boost newModelQuery()
 * @method static Builder<static>|Boost newQuery()
 * @method static Builder<static>|Boost query()
 * @method static Builder<static>|Boost whereBoostType($value)
 * @method static Builder<static>|Boost whereCreatedAt($value)
 * @method static Builder<static>|Boost whereExpiresAt($value)
 * @method static Builder<static>|Boost whereId($value)
 * @method static Builder<static>|Boost whereStartedAt($value)
 * @method static Builder<static>|Boost whereStatus($value)
 * @method static Builder<static>|Boost whereUpdatedAt($value)
 * @method static Builder<static>|Boost whereUserId($value)
 *
 * @mixin \Eloquent
 */
class Boost extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'started_at',
        'expires_at',
        'boost_type',
        'status',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active')
            ->where('expires_at', '>', now());
    }
}
