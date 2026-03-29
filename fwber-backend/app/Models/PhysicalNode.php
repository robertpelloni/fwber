<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $merchant_profile_id
 * @property string $node_uuid
 * @property string $node_type
 * @property string $name
 * @property float $latitude
 * @property float $longitude
 * @property bool $is_online
 * @property \Illuminate\Support\Carbon|null $last_heartbeat_at
 * @property array<array-key, mixed>|null $config
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\MerchantProfile $merchant
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PhysicalNode newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PhysicalNode newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PhysicalNode query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PhysicalNode whereConfig($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PhysicalNode whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PhysicalNode whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PhysicalNode whereIsOnline($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PhysicalNode whereLastHeartbeatAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PhysicalNode whereLatitude($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PhysicalNode whereLongitude($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PhysicalNode whereMerchantProfileId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PhysicalNode whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PhysicalNode whereNodeType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PhysicalNode whereNodeUuid($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PhysicalNode whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class PhysicalNode extends Model
{
    protected $fillable = [
        'merchant_profile_id',
        'node_uuid',
        'node_type',
        'name',
        'latitude',
        'longitude',
        'is_online',
        'last_heartbeat_at',
        'config',
    ];

    protected $casts = [
        'latitude' => 'float',
        'longitude' => 'float',
        'is_online' => 'boolean',
        'last_heartbeat_at' => 'datetime',
        'config' => 'array',
    ];

    public function merchant()
    {
        return $this->belongsTo(MerchantProfile::class, 'merchant_profile_id');
    }
}
