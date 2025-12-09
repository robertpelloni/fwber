<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use NotificationChannels\WebPush\HasPushSubscriptions;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, HasPushSubscriptions;

    protected $fillable = [
        'name',
        'email',
        'password',
        'tier',
        'tier_expires_at',
        'unlimited_swipes',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'tier_expires_at' => 'datetime',
        'unlimited_swipes' => 'boolean',
    ];

    // Relationships

    public function profile()
    {
        return $this->hasOne(UserProfile::class);
    }

    public function photos()
    {
        return $this->hasMany(Photo::class);
    }

    public function boosts()
    {
        return $this->hasMany(Boost::class);
    }

    public function events()
    {
        return $this->hasMany(Event::class, 'created_by_user_id');
    }

    public function attendingEvents()
    {
        return $this->belongsToMany(Event::class, 'event_attendees')
                    ->withPivot('status')
                    ->withTimestamps();
    }

    public function createdGroups()
    {
        return $this->hasMany(Group::class, 'created_by_user_id');
    }

    public function groups()
    {
        return $this->belongsToMany(Group::class, 'group_members')
                    ->withPivot('role', 'joined_at', 'is_active')
                    ->withTimestamps();
    }

    public function notificationPreferences()
    {
        return $this->hasMany(NotificationPreference::class);
    }

    public function matchesAsUser1()
    {
        return $this->hasMany(MatchModel::class, 'user1_id');
    }

    public function matchesAsUser2()
    {
        return $this->hasMany(MatchModel::class, 'user2_id');
    }
}
