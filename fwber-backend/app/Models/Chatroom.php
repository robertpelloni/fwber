<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

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
    ];

    protected $casts = [
        'is_public' => 'boolean',
        'is_active' => 'boolean',
        'member_count' => 'integer',
        'message_count' => 'integer',
        'last_activity_at' => 'datetime',
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
