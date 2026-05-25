<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $creator_id
 * @property int|null $scanner_id
 * @property int|null $chatroom_id
 * @property string $token
 * @property \Illuminate\Support\Carbon $expires_at
 * @property \Illuminate\Support\Carbon|null $used_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Chatroom|null $chatroom
 * @property-read \App\Models\User $creator
 * @property-read \App\Models\User|null $scanner
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BurnerLink newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BurnerLink newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BurnerLink query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BurnerLink whereChatroomId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BurnerLink whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BurnerLink whereCreatorId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BurnerLink whereExpiresAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BurnerLink whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BurnerLink whereScannerId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BurnerLink whereToken($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BurnerLink whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BurnerLink whereUsedAt($value)
 *
 * @mixin \Eloquent
 */
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
        return ! is_null($this->used_at);
    }
}
