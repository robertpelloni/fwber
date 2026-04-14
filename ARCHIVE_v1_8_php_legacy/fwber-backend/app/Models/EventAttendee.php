<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $event_id
 * @property int $user_id
 * @property string $status
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property bool $paid
 * @property string|null $payment_method
 * @property string|null $transaction_id
 * @property-read \App\Models\Event $event
 * @property-read \App\Models\User $user
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventAttendee newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventAttendee newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventAttendee query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventAttendee whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventAttendee whereEventId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventAttendee whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventAttendee wherePaid($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventAttendee wherePaymentMethod($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventAttendee whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventAttendee whereTransactionId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventAttendee whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventAttendee whereUserId($value)
 *
 * @mixin \Eloquent
 */
class EventAttendee extends Model
{
    use HasFactory;

    protected $fillable = [
        'event_id',
        'user_id',
        'status',
        'paid',
        'payment_method',
        'transaction_id',
    ];

    protected $casts = [
        'paid' => 'boolean',
    ];

    public function event()
    {
        return $this->belongsTo(Event::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
