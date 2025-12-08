<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

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
