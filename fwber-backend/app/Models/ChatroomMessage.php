<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $chatroom_id
 * @property int $user_id
 * @property int|null $parent_id
 * @property string $content
 * @property string $type
 * @property array<array-key, mixed>|null $metadata
 * @property bool $is_edited
 * @property bool $is_deleted
 * @property bool $is_pinned
 * @property bool $is_announcement
 * @property int $reaction_count
 * @property int $reply_count
 * @property \Illuminate\Support\Carbon|null $edited_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Chatroom $chatroom
 * @property-read string $display_content
 * @property-read string $display_user
 * @property-read string $preview
 * @property-read array $reaction_summary
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ChatroomMessageMention> $mentions
 * @property-read int|null $mentions_count
 * @property-read ChatroomMessage|null $parent
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ChatroomMessageReaction> $reactions
 * @property-read int|null $reactions_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, ChatroomMessage> $replies
 * @property-read int|null $replies_count
 * @property-read \App\Models\User $user
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ChatroomMessage announcements()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ChatroomMessage byType(string $type)
 * @method static \Database\Factories\ChatroomMessageFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ChatroomMessage newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ChatroomMessage newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ChatroomMessage notDeleted()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ChatroomMessage pinned()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ChatroomMessage query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ChatroomMessage recent(int $limit = 50)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ChatroomMessage replies()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ChatroomMessage topLevel()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ChatroomMessage whereChatroomId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ChatroomMessage whereContent($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ChatroomMessage whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ChatroomMessage whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ChatroomMessage whereEditedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ChatroomMessage whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ChatroomMessage whereIsAnnouncement($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ChatroomMessage whereIsDeleted($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ChatroomMessage whereIsEdited($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ChatroomMessage whereIsPinned($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ChatroomMessage whereMetadata($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ChatroomMessage whereParentId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ChatroomMessage whereReactionCount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ChatroomMessage whereReplyCount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ChatroomMessage whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ChatroomMessage whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ChatroomMessage whereUserId($value)
 * @mixin \Eloquent
 */
class ChatroomMessage extends Model
{
    use HasFactory;

    protected $fillable = [
        'chatroom_id',
        'user_id',
        'parent_id',
        'content',
        // Normalize to use 'type' column like migration defines
        'type',
        'metadata',
        'is_edited',
        'is_deleted',
        'is_pinned',
        'is_announcement',
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
        'reaction_count' => 'integer',
        'reply_count' => 'integer',
        'edited_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Get the chatroom this message belongs to
     */
    public function chatroom(): BelongsTo
    {
        return $this->belongsTo(Chatroom::class);
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
        return $this->belongsTo(ChatroomMessage::class, 'parent_id');
    }

    /**
     * Get replies to this message
     */
    public function replies(): HasMany
    {
        return $this->hasMany(ChatroomMessage::class, 'parent_id')
            ->where('is_deleted', false)
            ->orderBy('created_at', 'asc');
    }

    /**
     * Get reactions to this message
     */
    public function reactions(): HasMany
    {
        return $this->hasMany(ChatroomMessageReaction::class);
    }

    /**
     * Get mentions in this message
     */
    public function mentions(): HasMany
    {
        return $this->hasMany(ChatroomMessageMention::class);
    }

    /**
     * Check if message is a reply
     */
    public function isReply(): bool
    {
        return ! is_null($this->parent_id);
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

        return strlen($content) > 100 ? substr($content, 0, 100).'...' : $content;
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
        return $query->where('type', $type);
    }

    /**
     * Scope for recent messages
     */
    public function scopeRecent($query, int $limit = 50)
    {
        return $query->orderBy('created_at', 'desc')->limit($limit);
    }
}
