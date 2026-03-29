<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $user_id
 * @property string $public_key
 * @property string $key_type
 * @property string|null $device_id
 * @property \Illuminate\Support\Carbon|null $last_rotated_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\User $user
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserPublicKey newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserPublicKey newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserPublicKey query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserPublicKey whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserPublicKey whereDeviceId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserPublicKey whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserPublicKey whereKeyType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserPublicKey whereLastRotatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserPublicKey wherePublicKey($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserPublicKey whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserPublicKey whereUserId($value)
 * @mixin \Eloquent
 */
class UserPublicKey extends Model
{
    protected $fillable = [
        'user_id',
        'public_key',
        'key_type',
        'device_id',
        'last_rotated_at',
    ];

    protected $casts = [
        'last_rotated_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
