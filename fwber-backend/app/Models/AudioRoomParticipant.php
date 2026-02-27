<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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
