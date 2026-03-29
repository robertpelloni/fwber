<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $chatroom_message_id
 * @property int $user_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\User|null $mentionedUser
 * @property-read \App\Models\ChatroomMessage $message
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ChatroomMessageMention byUser(int $userId)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ChatroomMessageMention newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ChatroomMessageMention newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ChatroomMessageMention query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ChatroomMessageMention whereChatroomMessageId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ChatroomMessageMention whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ChatroomMessageMention whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ChatroomMessageMention whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ChatroomMessageMention whereUserId($value)
 *
 * @mixin \Eloquent
 */
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
