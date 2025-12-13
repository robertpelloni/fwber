<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Event extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'description',
        'location_name',
        'latitude',
        'longitude',
        'starts_at',
        'ends_at',
        'max_attendees',
        'price',
        'created_by_user_id',
        'status',
        'reminder_sent',
    ];

    protected $casts = [
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'latitude' => 'float',
        'longitude' => 'float',
        'price' => 'decimal:2',
        'reminder_sent' => 'boolean',
    ];

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function attendees()
    {
        return $this->hasMany(EventAttendee::class);
    }
    
    public function attendingUsers()
    {
        return $this->belongsToMany(User::class, 'event_attendees')
                    ->withPivot('status')
                    ->withTimestamps();
    }
}
