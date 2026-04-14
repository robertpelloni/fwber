<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $caller_id
 * @property int $receiver_id
 * @property \Illuminate\Support\Carbon|null $started_at
 * @property \Illuminate\Support\Carbon|null $ended_at
 * @property string $status
 * @property int|null $duration Duration in seconds
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\User $caller
 * @property-read \App\Models\User $receiver
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VideoCall newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VideoCall newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VideoCall query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VideoCall whereCallerId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VideoCall whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VideoCall whereDuration($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VideoCall whereEndedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VideoCall whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VideoCall whereReceiverId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VideoCall whereStartedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VideoCall whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VideoCall whereUpdatedAt($value)
 *
 * @mixin \Eloquent
 */
class VideoCall extends Model
{
    protected $fillable = [
        'caller_id',
        'receiver_id',
        'started_at',
        'ended_at',
        'status',
        'duration',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'ended_at' => 'datetime',
    ];

    public function caller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'caller_id');
    }

    public function receiver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'receiver_id');
    }
}
