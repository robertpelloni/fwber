<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

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
