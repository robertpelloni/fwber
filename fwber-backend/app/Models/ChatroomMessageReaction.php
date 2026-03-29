<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $chatroom_message_id
 * @property int $user_id
 * @property string $emoji
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\ChatroomMessage $message
 * @property-read \App\Models\User $user
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ChatroomMessageReaction byEmoji(string $emoji)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ChatroomMessageReaction byUser(int $userId)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ChatroomMessageReaction newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ChatroomMessageReaction newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ChatroomMessageReaction query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ChatroomMessageReaction whereChatroomMessageId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ChatroomMessageReaction whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ChatroomMessageReaction whereEmoji($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ChatroomMessageReaction whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ChatroomMessageReaction whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ChatroomMessageReaction whereUserId($value)
 * @mixin \Eloquent
 */
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
