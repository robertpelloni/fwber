<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GroupMember extends Model
{
    protected $fillable = [
        'group_id',
        'user_id',
        'role',
        'joined_at',
        'role_changed_at',
        'left_at',
        'is_active',
        'is_muted',
        'is_banned',
        'muted_until',
        'mute_reason',
        'muted_by_user_id',
        'banned_reason',
        'banned_at',
        'banned_by_user_id',
    ];

    protected $casts = [
        'joined_at' => 'datetime',
        'role_changed_at' => 'datetime',
        'left_at' => 'datetime',
        'is_active' => 'boolean',
        'is_muted' => 'boolean',
        'is_banned' => 'boolean',
        'muted_until' => 'datetime',
        'banned_at' => 'datetime',
    ];

    public function group(): BelongsTo
    {
        return $this->belongsTo(Group::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isAdmin(): bool
    {
        return in_array($this->role, ['owner', 'admin'], true);
    }

    public function isOwner(): bool
    {
        return $this->role === 'owner';
    }

    public function canModerate(): bool
    {
        return in_array($this->role, ['owner', 'admin', 'moderator'], true);
    }

    public function isBanned(): bool
    {
        return (bool)($this->is_banned ?? false);
    }

    public function isCurrentlyMuted(): bool
    {
        if (!(bool)($this->is_muted ?? false)) {
            return false;
        }
        if ($this->muted_until && now()->greaterThan($this->muted_until)) {
            return false; // expired
        }
        return true;
    }
}
