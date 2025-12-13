<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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
