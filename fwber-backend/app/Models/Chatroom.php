<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $name
 * @property string|null $description
 * @property string $type
 * @property string|null $category
 * @property string|null $city
 * @property string|null $neighborhood
 * @property int $created_by
 * @property bool $is_public
 * @property bool $is_active
 * @property numeric $token_entry_fee
 * @property int $member_count
 * @property int $message_count
 * @property \Illuminate\Support\Carbon|null $last_activity_at
 * @property \Illuminate\Support\Carbon|null $expires_at
 * @property array<array-key, mixed>|null $settings
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\User> $activeMembers
 * @property-read int|null $active_members_count
 * @property-read \App\Models\User $creator
 * @property-read string $display_name
 * @property-read string $url
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\User> $members
 * @property-read int|null $members_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ChatroomMessage> $messages
 * @property-read int|null $messages_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\User> $moderators
 * @property-read int|null $moderators_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ChatroomMessage> $recentMessages
 * @property-read int|null $recent_messages_count
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Chatroom active()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Chatroom byCategory(string $category)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Chatroom byCity(string $city)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Chatroom city()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Chatroom event()
 * @method static \Database\Factories\ChatroomFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Chatroom interest()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Chatroom newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Chatroom newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Chatroom public()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Chatroom query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Chatroom whereCategory($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Chatroom whereCity($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Chatroom whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Chatroom whereCreatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Chatroom whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Chatroom whereExpiresAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Chatroom whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Chatroom whereIsActive($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Chatroom whereIsPublic($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Chatroom whereLastActivityAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Chatroom whereMemberCount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Chatroom whereMessageCount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Chatroom whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Chatroom whereNeighborhood($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Chatroom whereSettings($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Chatroom whereTokenEntryFee($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Chatroom whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Chatroom whereUpdatedAt($value)
 *
 * @mixin \Eloquent
 */
class Chatroom extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'type',
        'category',
        'city',
        'neighborhood',
        'created_by',
        'is_public',
        'is_active',
        'member_count',
        'message_count',
        'last_activity_at',
        'settings',
        'token_entry_fee',
        'expires_at',
    ];

    protected $casts = [
        'is_public' => 'boolean',
        'is_active' => 'boolean',
        'member_count' => 'integer',
        'message_count' => 'integer',
        'last_activity_at' => 'datetime',
        'expires_at' => 'datetime',
        'settings' => 'array',
    ];

    /**
     * Get the user who created the chatroom
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get all members of the chatroom
     */
    public function members(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'chatroom_members')
            ->withPivot(['role', 'is_muted', 'is_banned', 'joined_at', 'last_seen_at', 'preferences'])
            ->withTimestamps();
    }

    /**
     * Get all messages in the chatroom
     */
    public function messages(): HasMany
    {
        return $this->hasMany(ChatroomMessage::class);
    }

    /**
     * Get recent messages (last 50)
     */
    public function recentMessages(): HasMany
    {
        return $this->hasMany(ChatroomMessage::class)
            ->where('is_deleted', false)
            ->orderBy('created_at', 'desc')
            ->limit(50);
    }

    /**
     * Get active members (not banned)
     */
    public function activeMembers(): BelongsToMany
    {
        return $this->members()->wherePivot('is_banned', false);
    }

    /**
     * Get moderators and admins
     */
    public function moderators(): BelongsToMany
    {
        return $this->members()->wherePivotIn('role', ['moderator', 'admin']);
    }

    /**
     * Check if user is a member of this chatroom
     */
    public function hasMember(User $user): bool
    {
        return $this->members()->where('user_id', $user->id)->exists();
    }

    /**
     * Check if user is a moderator or admin
     */
    public function hasModerator(User $user): bool
    {
        return $this->members()
            ->where('user_id', $user->id)
            ->wherePivotIn('role', ['moderator', 'admin'])
            ->exists();
    }

    /**
     * Check if user is banned
     */
    public function isBanned(User $user): bool
    {
        return $this->members()
            ->where('user_id', $user->id)
            ->wherePivot('is_banned', true)
            ->exists();
    }

    /**
     * Add a member to the chatroom
     */
    public function addMember(User $user, string $role = 'member'): void
    {
        $this->members()->attach($user->id, [
            'role' => $role,
            'joined_at' => now(),
            'last_seen_at' => now(),
        ]);

        $this->increment('member_count');
    }

    /**
     * Remove a member from the chatroom
     */
    public function removeMember(User $user): void
    {
        $this->members()->detach($user->id);
        $this->decrement('member_count');
    }

    /**
     * Update member role
     */
    public function updateMemberRole(User $user, string $role): void
    {
        $this->members()->updateExistingPivot($user->id, [
            'role' => $role,
        ]);
    }

    /**
     * Ban a member
     */
    public function banMember(User $user): void
    {
        $this->members()->updateExistingPivot($user->id, [
            'is_banned' => true,
        ]);
    }

    /**
     * Unban a member
     */
    public function unbanMember(User $user): void
    {
        $this->members()->updateExistingPivot($user->id, [
            'is_banned' => false,
        ]);
    }

    /**
     * Mute a member
     */
    public function muteMember(User $user): void
    {
        $this->members()->updateExistingPivot($user->id, [
            'is_muted' => true,
        ]);
    }

    /**
     * Unmute a member
     */
    public function unmuteMember(User $user): void
    {
        $this->members()->updateExistingPivot($user->id, [
            'is_muted' => false,
        ]);
    }

    /**
     * Update last activity timestamp
     */
    public function updateActivity(): void
    {
        $this->update(['last_activity_at' => now()]);
    }

    /**
     * Get chatroom URL
     */
    public function getUrlAttribute(): string
    {
        return "/chatrooms/{$this->id}";
    }

    /**
     * Get display name with type prefix
     */
    public function getDisplayNameAttribute(): string
    {
        $prefixes = [
            'interest' => '💬',
            'city' => '🏙️',
            'event' => '🎉',
            'private' => '🔒',
            'group' => '👥',
        ];

        $prefix = $prefixes[$this->type] ?? '💬';

        return "{$prefix} {$this->name}";
    }

    /**
     * Scope for active chatrooms
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for public chatrooms
     */
    public function scopePublic($query)
    {
        return $query->where('is_public', true);
    }

    /**
     * Scope for interest-based chatrooms
     */
    public function scopeInterest($query)
    {
        return $query->where('type', 'interest');
    }

    /**
     * Scope for city-based chatrooms
     */
    public function scopeCity($query)
    {
        return $query->where('type', 'city');
    }

    /**
     * Scope for event-based chatrooms
     */
    public function scopeEvent($query)
    {
        return $query->where('type', 'event');
    }

    /**
     * Scope for chatrooms by category
     */
    public function scopeByCategory($query, string $category)
    {
        return $query->where('category', $category);
    }

    /**
     * Scope for chatrooms by city
     */
    public function scopeByCity($query, string $city)
    {
        return $query->where('city', $city);
    }
}
