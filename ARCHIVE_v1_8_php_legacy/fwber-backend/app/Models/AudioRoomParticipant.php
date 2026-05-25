<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $audio_room_id
 * @property int $user_id
 * @property string $role
 * @property bool $is_muted
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\AudioRoom $room
 * @property-read \App\Models\User $user
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AudioRoomParticipant newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AudioRoomParticipant newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AudioRoomParticipant query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AudioRoomParticipant whereAudioRoomId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AudioRoomParticipant whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AudioRoomParticipant whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AudioRoomParticipant whereIsMuted($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AudioRoomParticipant whereRole($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AudioRoomParticipant whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AudioRoomParticipant whereUserId($value)
 *
 * @mixin \Eloquent
 */
class AudioRoomParticipant extends Model
{
    use HasFactory;

    protected $fillable = [
        'audio_room_id',
        'user_id',
        'role',
        'is_muted',
    ];

    protected $casts = [
        'is_muted' => 'boolean',
    ];

    public function room()
    {
        return $this->belongsTo(AudioRoom::class, 'audio_room_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
