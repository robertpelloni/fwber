<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $user_id
 * @property string $token_uuid The unique BLE broadcast ID of the hardware token
 * @property string $hardware_model
 * @property bool $is_active
 * @property int|null $battery_level
 * @property \Illuminate\Support\Carbon|null $last_seen_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\User $user
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HardwareToken newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HardwareToken newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HardwareToken query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HardwareToken whereBatteryLevel($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HardwareToken whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HardwareToken whereHardwareModel($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HardwareToken whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HardwareToken whereIsActive($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HardwareToken whereLastSeenAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HardwareToken whereTokenUuid($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HardwareToken whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HardwareToken whereUserId($value)
 * @mixin \Eloquent
 */
class HardwareToken extends Model
{
    protected $fillable = [
        'user_id',
        'token_uuid',
        'hardware_model',
        'is_active',
        'battery_level',
        'last_seen_at',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'battery_level' => 'integer',
        'last_seen_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
