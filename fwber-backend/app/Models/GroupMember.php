<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $group_id
 * @property int $user_id
 * @property string $role
 * @property \Illuminate\Support\Carbon $joined_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property bool $is_active
 * @property bool $is_banned
 * @property \Illuminate\Support\Carbon|null $banned_at
 * @property int|null $banned_by_user_id
 * @property string|null $banned_reason
 * @property bool $is_muted
 * @property \Illuminate\Support\Carbon|null $muted_until
 * @property string|null $mute_reason
 * @property int|null $muted_by_user_id
 * @property \Illuminate\Support\Carbon|null $left_at
 * @property \Illuminate\Support\Carbon|null $role_changed_at
 * @property-read \App\Models\Group $group
 * @property-read \App\Models\User $user
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GroupMember newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GroupMember newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GroupMember query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GroupMember whereBannedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GroupMember whereBannedByUserId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GroupMember whereBannedReason($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GroupMember whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GroupMember whereGroupId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GroupMember whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GroupMember whereIsActive($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GroupMember whereIsBanned($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GroupMember whereIsMuted($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GroupMember whereJoinedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GroupMember whereLeftAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GroupMember whereMuteReason($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GroupMember whereMutedByUserId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GroupMember whereMutedUntil($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GroupMember whereRole($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GroupMember whereRoleChangedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GroupMember whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GroupMember whereUserId($value)
 * @mixin \Eloquent
 */
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
