<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProximityChatroomMessageReaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'proximity_chatroom_message_id',
        'user_id',
        'emoji',
    ];

    /**
     * Get the message this reaction belongs to
     */
    public function message(): BelongsTo
    {
        return $this->belongsTo(ProximityChatroomMessage::class, 'proximity_chatroom_message_id');
    }

    /**
     * Get the user who made this reaction
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
