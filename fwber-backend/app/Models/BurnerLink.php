<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class BurnerLink extends Model
{
    use HasFactory;

    protected $fillable = [
        'creator_id',
        'scanner_id',
        'chatroom_id',
        'token',
        'expires_at',
        'used_at',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'used_at' => 'datetime',
    ];

    public function creator()
    {
        return $this->belongsTo(User::class, 'creator_id');
    }

    public function scanner()
    {
        return $this->belongsTo(User::class, 'scanner_id');
    }

    public function chatroom()
    {
        return $this->belongsTo(Chatroom::class);
    }

    /**
     * Determine if the burner link has expired.
     */
    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    /**
     * Determine if the burner link was already used.
     */
    public function isUsed(): bool
    {
        return !is_null($this->used_at);
    }
}
