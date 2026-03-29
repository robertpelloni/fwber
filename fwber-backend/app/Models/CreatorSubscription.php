<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $user_id
 * @property int $creator_id
 * @property \Illuminate\Support\Carbon $expires_at
 * @property int $cost
 * @property string $status
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\User $creator
 * @property-read \App\Models\User $user
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CreatorSubscription active()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CreatorSubscription newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CreatorSubscription newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CreatorSubscription query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CreatorSubscription whereCost($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CreatorSubscription whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CreatorSubscription whereCreatorId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CreatorSubscription whereExpiresAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CreatorSubscription whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CreatorSubscription whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CreatorSubscription whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CreatorSubscription whereUserId($value)
 *
 * @mixin \Eloquent
 */
class CreatorSubscription extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'creator_id',
        'expires_at',
        'cost',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'creator_id');
    }

    public function scopeActive($query)
    {
        return $query->where('expires_at', '>', now());
    }
}
