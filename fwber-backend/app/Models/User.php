<?php

namespace App\Models;

use App\Models\Traits\HasTwoFactorAuthentication;
use App\Models\Traits\SafelyHydratesAttributes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

/**
 * @property int $id
 * @property string $role
 * @property string $name
 * @property string $email
 * @property string $tier
 * @property \Illuminate\Support\Carbon|null $tier_expires_at
 * @property bool $unlimited_swipes
 * @property int $golden_tickets_remaining
 * @property string|null $avatar_url
 * @property \Illuminate\Support\Carbon|null $email_verified_at
 * @property \Illuminate\Support\Carbon|null $onboarding_completed_at
 * @property string $password
 * @property string|null $decoy_password
 * @property int|null $decoy_user_id
 * @property bool $is_decoy
 * @property \Illuminate\Support\Carbon|null $last_active_at
 */
class User extends Authenticatable
{
    use HasApiTokens, HasFactory, HasTwoFactorAuthentication, Notifiable, SafelyHydratesAttributes;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'tier',
        'tier_expires_at',
        'unlimited_swipes',
        'golden_tickets_remaining',
        'avatar_url',
        'onboarding_completed_at',
        'decoy_password',
        'decoy_user_id',
        'is_decoy',
        'last_active_at',
    ];

    protected $hidden = [
        'password',
        'decoy_password',
        'two_factor_secret',
        'two_factor_recovery_codes',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'tier_expires_at' => 'datetime',
        'unlimited_swipes' => 'boolean',
        'two_factor_confirmed_at' => 'datetime',
        'last_active_at' => 'datetime',
        'onboarding_completed_at' => 'datetime',
        'is_decoy' => 'boolean',
    ];

    public function vouches(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Vouch::class, 'to_user_id');
    }

    public function profile(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(UserProfile::class);
    }

    public function photos(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Photo::class);
    }

    public function primaryPhoto(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(Photo::class)->where('is_primary', true);
    }

    public function notificationPreferences(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(NotificationPreference::class);
    }

    public function matchesAsUser1(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(UserMatch::class, 'user1_id');
    }

    public function matchesAsUser2(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(UserMatch::class, 'user2_id');
    }

    public function sentMessages(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Message::class, 'sender_id');
    }

    public function receivedMessages(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Message::class, 'receiver_id');
    }

    public function matchActions(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(MatchAction::class);
    }

    public function deviceTokens(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(DeviceToken::class);
    }

    public function routeNotificationForExpo()
    {
        return $this->deviceTokens()->pluck('token')->toArray();
    }

    public function blockedUsers(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(User::class, 'blocks', 'blocker_id', 'blocked_id')
            ->withTimestamps();
    }

    public function blockedByUsers(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(User::class, 'blocks', 'blocked_id', 'blocker_id')
            ->withTimestamps();
    }
}
