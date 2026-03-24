<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

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
