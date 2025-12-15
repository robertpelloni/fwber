<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GroupMember extends Model
{
    use HasFactory;

    protected $fillable = [
        'group_id',
        'user_id',
        'role',
        'joined_at',
        'is_active',
        'is_banned',
        'banned_reason',
        'banned_at',
        'banned_by_user_id',
        'is_muted',
        'muted_until',
        'mute_reason',
        'muted_by_user_id',
        'left_at',
        'role_changed_at',
    ];

    protected $casts = [
        'joined_at' => 'datetime',
        'banned_at' => 'datetime',
        'muted_until' => 'datetime',
        'left_at' => 'datetime',
        'role_changed_at' => 'datetime',
        'is_active' => 'boolean',
        'is_banned' => 'boolean',
        'is_muted' => 'boolean',
    ];

    public function group()
    {
        return $this->belongsTo(Group::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function isAdmin()
    {
        return in_array($this->role, ['admin', 'owner']);
    }

    public function isOwner()
    {
        return $this->role === 'owner';
    }
}
