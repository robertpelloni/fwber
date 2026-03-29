<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $user_id
 * @property int $target_profile_id
 * @property string $platform
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\User $targetProfile
 * @property-read \App\Models\User $user
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShareUnlock newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShareUnlock newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShareUnlock query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShareUnlock whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShareUnlock whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShareUnlock wherePlatform($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShareUnlock whereTargetProfileId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShareUnlock whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShareUnlock whereUserId($value)
 *
 * @mixin \Eloquent
 */
class ShareUnlock extends Model
{
    protected $fillable = [
        'user_id',
        'target_profile_id',
        'platform',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function targetProfile()
    {
        return $this->belongsTo(User::class, 'target_profile_id');
    }
}
