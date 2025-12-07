<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChatroomMessageMention extends Model
{
    use HasFactory;

    protected $fillable = [
        'chatroom_message_id',
        'mentioned_user_id',
        'position',
        'length',
    ];

    /**
     * Get the message this mention belongs to
     */
    public function message(): BelongsTo
    {
        return $this->belongsTo(ChatroomMessage::class, 'chatroom_message_id');
    }

    /**
     * Get the user who was mentioned
     */
    public function mentionedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'mentioned_user_id');
    }

    /**
     * Scope for mentions by user
     */
    public function scopeByUser($query, int $userId)
    {
        return $query->where('mentioned_user_id', $userId);
    }
}
