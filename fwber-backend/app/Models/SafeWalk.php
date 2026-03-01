<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SafeWalk extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'status', 'destination',
        'start_lat', 'start_lng',
        'current_lat', 'current_lng',
        'dest_lat', 'dest_lng',
        'started_at', 'ended_at',
    ];

    protected $casts = [
        'start_lat' => 'float', 'start_lng' => 'float',
        'current_lat' => 'float', 'current_lng' => 'float',
        'dest_lat' => 'float', 'dest_lng' => 'float',
        'started_at' => 'datetime', 'ended_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }
}
