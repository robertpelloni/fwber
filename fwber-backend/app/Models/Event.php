<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Event extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'description',
        'type',
        'location_name',
        'latitude',
        'longitude',
        'starts_at',
        'ends_at',
        'max_attendees',
        'price',
        'token_cost',
        'created_by_user_id',
        'status',
    ];

    protected $casts = [
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'latitude' => 'float',
        'longitude' => 'float',
        'price' => 'decimal:2',
        'token_cost' => 'decimal:2',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function attendees(): HasMany
    {
        return $this->hasMany(EventAttendee::class);
    }

    public function invitations(): HasMany
    {
        return $this->hasMany(EventInvitation::class);
    }
}
