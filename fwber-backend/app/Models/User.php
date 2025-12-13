<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use NotificationChannels\WebPush\HasPushSubscriptions;
use App\Models\Traits\HasTwoFactorAuthentication;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, HasPushSubscriptions, HasTwoFactorAuthentication;

    protected $fillable = [
        'name',
        'email',
        'password',
        'tier',
        'tier_expires_at',
        'unlimited_swipes',
        'referral_code',
        'referrer_id',
        'wallet_address',
        'token_balance',
        'current_streak',
        'last_active_at',
        'golden_tickets_remaining',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_secret',
        'two_factor_recovery_codes',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'tier_expires_at' => 'datetime',
        'unlimited_swipes' => 'boolean',
        'two_factor_confirmed_at' => 'datetime',
        'token_balance' => 'decimal:4',
        'last_active_at' => 'datetime',
        'current_streak' => 'integer',
        'golden_tickets_remaining' => 'integer',
    ];

    protected $appends = [
        'two_factor_enabled',
    ];

    // Relationships

    public function referrer()
    {
        return $this->belongsTo(User::class, 'referrer_id');
    }

    public function referrals()
    {
        return $this->hasMany(User::class, 'referrer_id');
    }

    public function tokenTransactions()
    {
        return $this->hasMany(TokenTransaction::class);
    }

    public function getTwoFactorEnabledAttribute(): bool
    {
        return $this->hasEnabledTwoFactorAuthentication();
    }

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
                    ->withPivot('role', 'joined_at')
                    ->withTimestamps();
    }

    public function notificationPreferences()
    {
        return $this->hasMany(NotificationPreference::class);
    }

    public function matchesAsUser1()
    {
        return $this->hasMany(UserMatch::class, 'user1_id');
    }

    public function matchesAsUser2()
    {
        return $this->hasMany(UserMatch::class, 'user2_id');
    }

    public function sentMessages()
    {
        return $this->hasMany(Message::class, 'sender_id');
    }

    public function receivedMessages()
    {
        return $this->hasMany(Message::class, 'receiver_id');
    }

    public function subscriptions()
    {
        return $this->hasMany(Subscription::class);
    }

    public function giftsSent()
    {
        return $this->hasMany(UserGift::class, 'sender_id');
    }

    public function giftsReceived()
    {
        return $this->hasMany(UserGift::class, 'receiver_id');
    }

    public function matchActions()
    {
        return $this->hasMany(MatchAction::class);
    }

    public function deviceTokens()
    {
        return $this->hasMany(DeviceToken::class);
    }

    public function friends()
    {
        return $this->belongsToMany(User::class, 'friends', 'user_id', 'friend_id')
            ->wherePivot('status', 'accepted');
    }
}
