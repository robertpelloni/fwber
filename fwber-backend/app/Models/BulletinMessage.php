<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $bulletin_board_id
 * @property int $user_id
 * @property string $content
 * @property bool $is_moderated
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property bool $is_anonymous
 * @property \Illuminate\Support\Carbon|null $expires_at
 * @property array<array-key, mixed>|null $metadata
 * @property int $reaction_count
 * @property int $reply_count
 * @property int|null $parent_message_id
 * @property-read \App\Models\BulletinBoard $bulletinBoard
 * @property-read string|null $author_avatar
 * @property-read string $author_name
 * @property-read BulletinMessage|null $parentMessage
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\BulletinReaction> $reactions
 * @property-read int|null $reactions_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, BulletinMessage> $replies
 * @property-read int|null $replies_count
 * @property-read \App\Models\User $user
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BulletinMessage newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BulletinMessage newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BulletinMessage notExpired()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BulletinMessage notModerated()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BulletinMessage query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BulletinMessage recent(int $hours = 24)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BulletinMessage whereBulletinBoardId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BulletinMessage whereContent($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BulletinMessage whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BulletinMessage whereExpiresAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BulletinMessage whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BulletinMessage whereIsAnonymous($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BulletinMessage whereIsModerated($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BulletinMessage whereMetadata($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BulletinMessage whereParentMessageId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BulletinMessage whereReactionCount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BulletinMessage whereReplyCount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BulletinMessage whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BulletinMessage whereUserId($value)
 * @mixin \Eloquent
 */
class BulletinMessage extends Model
{
    use HasFactory;

    protected $fillable = [
        'bulletin_board_id',
        'user_id',
        'content',
        'metadata',
        'is_anonymous',
        'is_moderated',
        'expires_at',
        'reaction_count',
        'reply_count',
    ];

    protected $casts = [
        'metadata' => 'array',
        'is_anonymous' => 'boolean',
        'is_moderated' => 'boolean',
        'expires_at' => 'datetime',
    ];

    /**
     * Get the bulletin board this message belongs to
     */
    public function bulletinBoard(): BelongsTo
    {
        return $this->belongsTo(BulletinBoard::class);
    }

    /**
     * Get the user who posted this message
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get reactions for this message
     */
    public function reactions(): HasMany
    {
        return $this->hasMany(BulletinReaction::class);
    }

    /**
     * Get replies to this message
     */
    public function replies(): HasMany
    {
        return $this->hasMany(BulletinMessage::class, 'parent_message_id');
    }

    /**
     * Get the parent message if this is a reply
     */
    public function parentMessage(): BelongsTo
    {
        return $this->belongsTo(BulletinMessage::class, 'parent_message_id');
    }

    /**
     * Check if message has expired
     */
    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    /**
     * Get display name for the message author
     */
    public function getAuthorNameAttribute(): string
    {
        if ($this->is_anonymous) {
            return 'Anonymous';
        }

        return $this->user->name ?? 'Unknown User';
    }

    /**
     * Get display avatar for the message author
     */
    public function getAuthorAvatarAttribute(): ?string
    {
        if ($this->is_anonymous) {
            return null;
        }

        return $this->user->avatar_url ?? null;
    }

    /**
     * Scope for non-expired messages
     */
    public function scopeNotExpired($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('expires_at')
                ->orWhere('expires_at', '>', now());
        });
    }

    /**
     * Scope for non-moderated messages
     */
    public function scopeNotModerated($query)
    {
        return $query->where('is_moderated', false);
    }

    /**
     * Scope for recent messages
     */
    public function scopeRecent($query, int $hours = 24)
    {
        return $query->where('created_at', '>=', now()->subHours($hours));
    }

    /**
     * Boot method to update board activity when message is created
     */
    protected static function boot()
    {
        parent::boot();

        static::created(function ($message) {
            $message->bulletinBoard->updateActivity();
        });

        static::deleted(function ($message) {
            $message->bulletinBoard->updateActivity();
        });
    }
}
