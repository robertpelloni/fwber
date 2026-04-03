<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $proximity_chatroom_message_id
 * @property int $user_id
 * @property string $emoji
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\ProximityChatroomMessage $message
 * @property-read \App\Models\User $user
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProximityChatroomMessageReaction newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProximityChatroomMessageReaction newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProximityChatroomMessageReaction query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProximityChatroomMessageReaction whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProximityChatroomMessageReaction whereEmoji($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProximityChatroomMessageReaction whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProximityChatroomMessageReaction whereProximityChatroomMessageId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProximityChatroomMessageReaction whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProximityChatroomMessageReaction whereUserId($value)
 *
 * @mixin \Eloquent
 */
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
