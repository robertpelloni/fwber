<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VenueCheckin extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'venue_id',
        'message',
        'checked_out_at',
        'created_at',
    ];

    protected $casts = [
        'checked_out_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function venue()
    {
        return $this->belongsTo(Venue::class);
    }
}
