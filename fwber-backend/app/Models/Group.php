<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Group extends Model
{
    protected $fillable = [
        'name',
        'description',
        'visibility',
        'avatar_url',
        'creator_id',
        'max_members',
        'settings',
        'is_active',
    ];

    protected $casts = [
        'settings' => 'array',
        'is_active' => 'boolean',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creator_id');
    }

    public function members(): HasMany
    {
        return $this->hasMany(GroupMember::class);
    }

    public function activeMembers(): HasMany
    {
        return $this->hasMany(GroupMember::class)->where('is_active', true);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(GroupMessage::class);
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'group_members')
            ->wherePivot('is_active', true)
            ->withPivot(['role', 'joined_at', 'is_muted'])
            ->withTimestamps();
    }

    public function scopePublic($query)
    {
        return $query->where('visibility', 'public');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function hasMember(int $userId): bool
    {
        return $this->activeMembers()->where('user_id', $userId)->where('is_banned', false)->exists();
    }

    public function getMemberRole(int $userId): ?string
    {
        $member = $this->activeMembers()->where('user_id', $userId)->where('is_banned', false)->first();
        return $member?->role;
    }

    public function isFull(): bool
    {
        return $this->activeMembers()->count() >= $this->max_members;
    }
}
