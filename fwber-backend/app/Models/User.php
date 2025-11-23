<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'interests',
        'last_seen_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'interests' => 'array',
            'last_seen_at' => 'datetime',
        ];
    }

    public function profile()
    {
        return $this->hasOne(UserProfile::class);
    }

    public function tokens()
    {
        return $this->hasMany(ApiToken::class);
    }

    public function photos()
    {
        return $this->hasMany(Photo::class);
    }

    public function location()
    {
        return $this->hasOne(UserLocation::class);
    }

    public function deviceTokens()
    {
        return $this->hasMany(DeviceToken::class);
    }

    public function friends()
    {
        return $this->belongsToMany(User::class, 'friends', 'user_id', 'friend_id')
            ->wherePivot('status', 'accepted');
    public function matchesAsUser1()
    {
        return $this->hasMany(UserMatch::class, 'user1_id');
    }

    public function matchesAsUser2()
    {
        return $this->hasMany(UserMatch::class, 'user2_id');
    }
}
