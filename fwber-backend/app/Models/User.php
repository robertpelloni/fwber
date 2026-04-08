<?php

namespace App\Models;

use App\Models\Traits\HasTwoFactorAuthentication;
use App\Models\Traits\SafelyHydratesAttributes;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use NotificationChannels\WebPush\HasPushSubscriptions;

/**
 * @property int $id
 * @property string $role
 * @property string $name
 * @property string $email
 * @property string|null $actor_uri
 * @property int $is_remote
 * @property string|null $referral_code
 * @property int $golden_tickets_remaining
 * @property int|null $referrer_id
 * @property string|null $wallet_address
 * @property string|null $merchant_secret
 * @property string|null $merchant_name
 * @property numeric $token_balance
 * @property string|null $avatar_url
 * @property \Illuminate\Support\Carbon|null $email_verified_at
 * @property \Illuminate\Support\Carbon|null $onboarding_completed_at
 * @property string $password
 * @property string|null $decoy_password
 * @property int|null $decoy_user_id
 * @property bool $is_decoy
 * @property string|null $two_factor_secret
 * @property string|null $two_factor_recovery_codes
 * @property \Illuminate\Support\Carbon|null $two_factor_confirmed_at
 * @property string|null $remember_token
 * @property int $current_streak
 * @property \Illuminate\Support\Carbon|null $last_active_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property string $tier
 * @property \Illuminate\Support\Carbon|null $tier_expires_at
 * @property bool $unlimited_swipes
 * @property \Illuminate\Support\Carbon|null $last_daily_bonus_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Achievement> $achievements
 * @property-read int|null $achievements_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Event> $attendingEvents
 * @property-read int|null $attending_events_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Boost> $boosts
 * @property-read int|null $boosts_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Group> $createdGroups
 * @property-read int|null $created_groups_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\DeviceToken> $deviceTokens
 * @property-read int|null $device_tokens_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Event> $events
 * @property-read int|null $events_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, User> $friends
 * @property-read int|null $friends_count
 * @property-read bool $streak_just_updated
 * @property-read bool $two_factor_enabled
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\UserGift> $giftsReceived
 * @property-read int|null $gifts_received_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\UserGift> $giftsSent
 * @property-read int|null $gifts_sent_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Group> $groups
 * @property-read int|null $groups_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\MatchAction> $matchActions
 * @property-read int|null $match_actions_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\UserMatch> $matchesAsUser1
 * @property-read int|null $matches_as_user1_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\UserMatch> $matchesAsUser2
 * @property-read int|null $matches_as_user2_count
 * @property-read \App\Models\MerchantProfile|null $merchantProfile
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\NotificationPreference> $notificationPreferences
 * @property-read int|null $notification_preferences_count
 * @property-read \Illuminate\Notifications\DatabaseNotificationCollection<int, \Illuminate\Notifications\DatabaseNotification> $notifications
 * @property-read int|null $notifications_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Photo> $photos
 * @property-read int|null $photos_count
 * @property-read \App\Models\Photo|null $primaryPhoto
 * @property-read \App\Models\UserProfile|null $profile
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \NotificationChannels\WebPush\PushSubscription> $pushSubscriptions
 * @property-read int|null $push_subscriptions_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Message> $receivedMessages
 * @property-read int|null $received_messages_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, User> $referrals
 * @property-read int|null $referrals_count
 * @property-read User|null $referrer
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Message> $sentMessages
 * @property-read int|null $sent_messages_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Subscription> $subscriptions
 * @property-read int|null $subscriptions_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\TokenTransaction> $tokenTransactions
 * @property-read int|null $token_transactions_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \Laravel\Sanctum\PersonalAccessToken> $tokens
 * @property-read int|null $tokens_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Vouch> $vouches
 * @property-read int|null $vouches_count
 * @property-read \App\Models\UserProfile|null $profile
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Photo> $photos
 * @property-read \App\Models\Photo|null $primaryPhoto
 * @property-read \App\Models\MerchantProfile|null $merchantProfile
 * @property-read \App\Models\User|null $referrer
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\User> $referrals
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Vouch> $vouches
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\TokenTransaction> $tokenTransactions
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\UserMatch> $matches_as_user1
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\UserMatch> $matches_as_user2
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Message> $sent_messages
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Message> $received_messages
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Group> $groups
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Event> $events
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Subscription> $subscriptions
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\UserGift> $gifts_received
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\UserGift> $gifts_sent
 * @property-read bool $two_factor_enabled
 * @property-read bool $streak_just_updated
 *
 * @method static \Database\Factories\UserFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereActorUri($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereAvatarUrl($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereCurrentStreak($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereDecoyPassword($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereDecoyUserId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereEmailVerifiedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereGoldenTicketsRemaining($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereIsDecoy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereIsRemote($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereLastActiveAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereLastDailyBonusAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereMerchantName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereMerchantSecret($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereOnboardingCompletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User wherePassword($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereReferralCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereReferrerId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereRememberToken($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereRole($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereTier($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereTierExpiresAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereTokenBalance($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereTwoFactorConfirmedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereTwoFactorRecoveryCodes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereTwoFactorSecret($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereUnlimitedSwipes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereWalletAddress($value)
 *
 * @mixin \Eloquent
 */
class User extends Authenticatable implements MustVerifyEmail
{
    use HasApiTokens, HasFactory, HasPushSubscriptions, HasTwoFactorAuthentication, Notifiable, SafelyHydratesAttributes;

    protected $fillable = [
        'name',
        'email',
        'actor_uri',
        'is_remote',
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
        'onboarding_completed_at',
        'last_daily_bonus_at',
        'role',
        'decoy_password',
        'decoy_user_id',
        'is_decoy',
    ];

    protected $hidden = [
        'password',
        'decoy_password',
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
        'last_daily_bonus_at' => 'datetime',
        'onboarding_completed_at' => 'datetime',
        'is_decoy' => 'boolean',
    ];

    protected $appends = [
        // 'two_factor_enabled', // Commented out to prevent crashes during schema drift
        'streak_just_updated',
    ];

    /**
     * Transient property to track if streak was incremented in this request.
     * Not stored in database.
     */
    public $streakJustUpdated = false;

    public function getStreakJustUpdatedAttribute(): bool
    {
        return $this->streakJustUpdated;
    }

    // Relationships

    public function merchantProfile(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(MerchantProfile::class);
    }

    public function referrer(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class, 'referrer_id');
    }

    public function referrals(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(User::class, 'referrer_id');
    }

    public function vouches(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Vouch::class, 'to_user_id');
    }

    public function tokenTransactions(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(TokenTransaction::class);
    }

    public function getTwoFactorEnabledAttribute(): bool
    {
        return $this->hasEnabledTwoFactorAuthentication();
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

    public function boosts(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Boost::class);
    }

    public function events(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Event::class, 'created_by_user_id');
    }

    public function attendingEvents(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(Event::class, 'event_attendees')
            ->withPivot('status')
            ->withTimestamps();
    }

    public function createdGroups(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Group::class, 'created_by_user_id');
    }

    public function groups(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(Group::class, 'group_members')
            ->withPivot('role', 'joined_at')
            ->withTimestamps();
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

    public function subscriptions(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    public function giftsSent(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(UserGift::class, 'sender_id');
    }

    public function giftsReceived(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(UserGift::class, 'receiver_id');
    }

    public function matchActions(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(MatchAction::class);
    }

    public function deviceTokens(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(DeviceToken::class);
    }

    public function friends(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(User::class, 'friends', 'user_id', 'friend_id')
            ->wherePivot('status', 'accepted');
    }

    public function journals(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Journal::class);
    }

    public function outgoingRelationshipLinks(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(RelationshipLink::class);
    }

    public function followedTopics(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(Topic::class, 'topic_user_follows')
            ->withPivot('followed_at')
            ->withTimestamps();
    }

    public function incomingRelationshipLinks(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(RelationshipLink::class, 'related_user_id');
    }

    public function achievements(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(Achievement::class, 'user_achievements')
            ->withPivot('unlocked_at')
            ->withTimestamps();
    }

    public function governanceVotes(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(GovernanceVote::class);
    }

    /**
     * Send the password reset notification.
     * Uses our custom notification with frontend URL.
     */
    public function sendPasswordResetNotification($token): void
    {
        $this->notify(new \App\Notifications\ResetPasswordNotification($token));
    }
}
