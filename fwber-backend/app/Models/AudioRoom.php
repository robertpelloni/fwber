<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AudioRoom extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'topic',
        'host_id',
        'status',
    ];

    public function host()
    {
        return $this->belongsTo(User::class, 'host_id');
    }

    public function participants()
    {
        return $this->hasMany(AudioRoomParticipant::class);
    }
}
