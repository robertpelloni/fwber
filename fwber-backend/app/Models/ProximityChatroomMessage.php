<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProximityChatroomMessage extends Model
{
    use HasFactory;

    protected $fillable = [
        'proximity_chatroom_id',
        'user_id',
        'parent_id',
        'content',
        'message_type',
        'metadata',
        'is_edited',
        'is_deleted',
        'is_pinned',
        'is_announcement',
        'is_networking',
        'is_social',
        'reaction_count',
        'reply_count',
        'edited_at',
        'deleted_at',
    ];

    protected $casts = [
        'metadata' => 'array',
        'is_edited' => 'boolean',
        'is_deleted' => 'boolean',
        'is_pinned' => 'boolean',
        'is_announcement' => 'boolean',
        'is_networking' => 'boolean',
        'is_social' => 'boolean',
        'reaction_count' => 'integer',
        'reply_count' => 'integer',
        'edited_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Get the proximity chatroom this message belongs to
     */
    public function proximityChatroom(): BelongsTo
    {
        return $this->belongsTo(ProximityChatroom::class, 'proximity_chatroom_id');
    }

    /**
     * Get the user who sent this message
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the parent message (for replies)
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(ProximityChatroomMessage::class, 'parent_id');
    }

    /**
     * Get replies to this message
     */
    public function replies(): HasMany
    {
        return $this->hasMany(ProximityChatroomMessage::class, 'parent_id')
            ->where('is_deleted', false)
            ->orderBy('created_at', 'asc');
    }

    /**
     * Get reactions to this message
     */
    public function reactions(): HasMany
    {
        return $this->hasMany(ProximityChatroomMessageReaction::class);
    }

    /**
     * Get mentions in this message
     */
    public function mentions(): HasMany
    {
        return $this->hasMany(ProximityChatroomMessageMention::class);
    }

    /**
     * Check if message is a reply
     */
    public function isReply(): bool
    {
        return !is_null($this->parent_id);
    }

    /**
     * Check if message is a top-level message
     */
    public function isTopLevel(): bool
    {
        return is_null($this->parent_id);
    }

    /**
     * Get display content (handle deleted messages)
     */
    public function getDisplayContentAttribute(): string
    {
        if ($this->is_deleted) {
            return '[Message deleted]';
        }

        return $this->content;
    }

    /**
     * Get display name for deleted messages
     */
    public function getDisplayUserAttribute(): string
    {
        if ($this->is_deleted) {
            return '[Deleted User]';
        }

        return $this->user->name ?? 'Unknown User';
    }

    /**
     * Get message preview (first 100 characters)
     */
    public function getPreviewAttribute(): string
    {
        $content = $this->display_content;
        return strlen($content) > 100 ? substr($content, 0, 100) . '...' : $content;
    }

    /**
     * Get reaction summary
     */
    public function getReactionSummaryAttribute(): array
    {
        return $this->reactions()
            ->selectRaw('emoji, COUNT(*) as count')
            ->groupBy('emoji')
            ->pluck('count', 'emoji')
            ->toArray();
    }

    /**
     * Add reaction to message
     */
    public function addReaction(User $user, string $emoji): void
    {
        // Remove existing reaction from this user
        $this->reactions()->where('user_id', $user->id)->delete();

        // Add new reaction
        $this->reactions()->create([
            'user_id' => $user->id,
            'emoji' => $emoji,
        ]);

        $this->increment('reaction_count');
    }

    /**
     * Remove reaction from message
     */
    public function removeReaction(User $user, string $emoji): void
    {
        $deleted = $this->reactions()
            ->where('user_id', $user->id)
            ->where('emoji', $emoji)
            ->delete();

        if ($deleted) {
            $this->decrement('reaction_count');
        }
    }

    /**
     * Soft delete message
     */
    public function softDelete(): void
    {
        $this->update([
            'is_deleted' => true,
            'deleted_at' => now(),
            'content' => '[Message deleted]',
        ]);
    }

    /**
     * Edit message content
     */
    public function edit(string $newContent): void
    {
        $this->update([
            'content' => $newContent,
            'is_edited' => true,
            'edited_at' => now(),
        ]);
    }

    /**
     * Pin message
     */
    public function pin(): void
    {
        $this->update(['is_pinned' => true]);
    }

    /**
     * Unpin message
     */
    public function unpin(): void
    {
        $this->update(['is_pinned' => false]);
    }

    /**
     * Mark as announcement
     */
    public function markAsAnnouncement(): void
    {
        $this->update(['is_announcement' => true]);
    }

    /**
     * Mark as networking message
     */
    public function markAsNetworking(): void
    {
        $this->update(['is_networking' => true]);
    }

    /**
     * Mark as social message
     */
    public function markAsSocial(): void
    {
        $this->update(['is_social' => true]);
    }

    /**
     * Scope for non-deleted messages
     */
    public function scopeNotDeleted($query)
    {
        return $query->where('is_deleted', false);
    }

    /**
     * Scope for pinned messages
     */
    public function scopePinned($query)
    {
        return $query->where('is_pinned', true);
    }

    /**
     * Scope for announcements
     */
    public function scopeAnnouncements($query)
    {
        return $query->where('is_announcement', true);
    }

    /**
     * Scope for networking messages
     */
    public function scopeNetworking($query)
    {
        return $query->where('is_networking', true);
    }

    /**
     * Scope for social messages
     */
    public function scopeSocial($query)
    {
        return $query->where('is_social', true);
    }

    /**
     * Scope for top-level messages (not replies)
     */
    public function scopeTopLevel($query)
    {
        return $query->whereNull('parent_id');
    }

    /**
     * Scope for replies
     */
    public function scopeReplies($query)
    {
        return $query->whereNotNull('parent_id');
    }

    /**
     * Scope for messages by type
     */
    public function scopeByType($query, string $type)
    {
        return $query->where('message_type', $type);
    }

    /**
     * Scope for recent messages
     */
    public function scopeRecent($query, int $limit = 50)
    {
        return $query->orderBy('created_at', 'desc')->limit($limit);
    }
}
