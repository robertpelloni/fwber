<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SlowRequest extends Model
{
    protected $fillable = [
        'user_id',
        'method',
        'url',
        'duration_ms',
        'ip',
        'user_agent',
        'payload',
    ];

    protected $casts = [
        'duration_ms' => 'float',
        'payload' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
