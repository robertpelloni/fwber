<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $sender_id
 * @property int $receiver_id
 * @property int $gift_id
 * @property string|null $message
 * @property int $cost_at_time
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Gift $gift
 * @property-read \App\Models\User $receiver
 * @property-read \App\Models\User $sender
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserGift newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserGift newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserGift query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserGift whereCostAtTime($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserGift whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserGift whereGiftId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserGift whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserGift whereMessage($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserGift whereReceiverId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserGift whereSenderId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserGift whereUpdatedAt($value)
 *
 * @mixin \Eloquent
 */
class UserGift extends Model
{
    protected $fillable = [
        'sender_id',
        'receiver_id',
        'gift_id',
        'message',
        'cost_at_time',
    ];

    protected $casts = [
        'cost_at_time' => 'integer',
    ];

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    public function receiver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'receiver_id');
    }

    public function gift(): BelongsTo
    {
        return $this->belongsTo(Gift::class);
    }
}
