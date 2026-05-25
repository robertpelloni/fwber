<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $event_id
 * @property int $inviter_id
 * @property int $invitee_id
 * @property string $status
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Event $event
 * @property-read \App\Models\User $invitee
 * @property-read \App\Models\User $inviter
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventInvitation newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventInvitation newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventInvitation query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventInvitation whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventInvitation whereEventId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventInvitation whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventInvitation whereInviteeId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventInvitation whereInviterId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventInvitation whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventInvitation whereUpdatedAt($value)
 *
 * @mixin \Eloquent
 */
class EventInvitation extends Model
{
    protected $fillable = [
        'event_id',
        'inviter_id',
        'invitee_id',
        'status',
    ];

    public function event()
    {
        return $this->belongsTo(Event::class);
    }

    public function inviter()
    {
        return $this->belongsTo(User::class, 'inviter_id');
    }

    public function invitee()
    {
        return $this->belongsTo(User::class, 'invitee_id');
    }
}
