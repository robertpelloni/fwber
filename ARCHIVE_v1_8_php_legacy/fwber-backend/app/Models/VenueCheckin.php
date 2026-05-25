<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $user_id
 * @property int $venue_id
 * @property string|null $message
 * @property \Illuminate\Support\Carbon|null $checked_out_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\User $user
 * @property-read \App\Models\Venue $venue
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VenueCheckin newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VenueCheckin newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VenueCheckin query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VenueCheckin whereCheckedOutAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VenueCheckin whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VenueCheckin whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VenueCheckin whereMessage($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VenueCheckin whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VenueCheckin whereUserId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VenueCheckin whereVenueId($value)
 *
 * @mixin \Eloquent
 */
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
