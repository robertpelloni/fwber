<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $proximity_chatroom_message_id
 * @property int $user_id
 * @property int $offset
 * @property int $length
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProximityChatroomMessageMention newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProximityChatroomMessageMention newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProximityChatroomMessageMention query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProximityChatroomMessageMention whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProximityChatroomMessageMention whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProximityChatroomMessageMention whereLength($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProximityChatroomMessageMention whereOffset($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProximityChatroomMessageMention whereProximityChatroomMessageId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProximityChatroomMessageMention whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProximityChatroomMessageMention whereUserId($value)
 *
 * @mixin \Eloquent
 */
class ProximityChatroomMessageMention extends Model
{
    //
}
