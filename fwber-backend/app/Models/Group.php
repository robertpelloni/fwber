<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Group extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'icon',
        'privacy',
        'created_by_user_id',
        'creator_id',
        'member_count',
        'visibility',
        'is_active',
        'chatroom_id',
        'max_members',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'member_count' => 'integer',
        'max_members' => 'integer',
    ];

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function chatroom()
    {
        return $this->belongsTo(Chatroom::class);
    }

    public function members()
    {
        return $this->hasMany(GroupMember::class);
    }

    public function activeMembers()
    {
        // Assuming 'is_banned' column exists in group_members, or we just return all members for now if column is missing
        // But based on controller usage, it expects a filter.
        // Let's assume the column exists or will exist.
        return $this->members()->where('is_banned', false);
    }

    public function posts()
    {
        return $this->hasMany(GroupPost::class);
    }

    public function hasMember($userId)
    {
        return $this->members()->where('user_id', $userId)->exists();
    }

    public function getMemberRole($userId)
    {
        $member = $this->members()->where('user_id', $userId)->first();
        return $member ? $member->role : null;
    }

    public function isFull()
    {
        return $this->max_members > 0 && $this->member_count >= $this->max_members;
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopePublic($query)
    {
        return $query->where('privacy', 'public');
    }
}
