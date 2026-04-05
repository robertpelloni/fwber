<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Lightweight call log used by the restored WebRTC video chat surface.
 *
 * The frontend still contained a fully-fledged video modal, signaling hooks,
 * and call history UI. Restoring this model brings back the missing persistence
 * layer so those interfaces now have a real backend contract again.
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
