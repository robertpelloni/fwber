<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FederatedServer extends Model
{
    protected $fillable = [
        'domain',
        'software',
        'version',
        'is_blocked',
        'discovered_at',
    ];

    protected $casts = [
        'is_blocked' => 'boolean',
        'discovered_at' => 'datetime',
    ];
}
