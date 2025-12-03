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
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'member_count' => 'integer',
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

    public function posts()
    {
        return $this->hasMany(GroupPost::class);
    }

    public function hasMember($userId)
    {
        return $this->members()->where('user_id', $userId)->exists();
    }
}
