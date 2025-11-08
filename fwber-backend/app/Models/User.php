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
}
