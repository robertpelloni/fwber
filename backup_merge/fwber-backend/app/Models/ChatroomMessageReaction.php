<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChatroomMessageReaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'chatroom_message_id',
        'user_id',
        'emoji',
    ];

    /**
     * Get the message this reaction belongs to
     */
    public function message(): BelongsTo
    {
        return $this->belongsTo(ChatroomMessage::class, 'chatroom_message_id');
    }

    /**
     * Get the user who made this reaction
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope for reactions by emoji
     */
    public function scopeByEmoji($query, string $emoji)
    {
        return $query->where('emoji', $emoji);
    }

    /**
     * Scope for reactions by user
     */
    public function scopeByUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }
}
