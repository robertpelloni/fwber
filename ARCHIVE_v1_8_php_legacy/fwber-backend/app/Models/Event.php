<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $title
 * @property string $description
 * @property string $type
 * @property string $location_name
 * @property float $latitude
 * @property float $longitude
 * @property \Illuminate\Support\Carbon|null $starts_at
 * @property \Illuminate\Support\Carbon|null $ends_at
 * @property int|null $max_attendees
 * @property numeric|null $price
 * @property numeric|null $token_cost
 * @property int $created_by_user_id
 * @property string $status
 * @property bool $reminder_sent
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property int|null $chatroom_id
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\EventAttendee> $attendees
 * @property-read int|null $attendees_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\User> $attendingUsers
 * @property-read int|null $attending_users_count
 * @property-read \App\Models\Chatroom|null $chatroom
 * @property-read \App\Models\User $creator
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Group> $groups
 * @property-read int|null $groups_count
 *
 * @method static \Database\Factories\EventFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Event newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Event newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Event query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Event whereChatroomId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Event whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Event whereCreatedByUserId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Event whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Event whereEndsAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Event whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Event whereLatitude($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Event whereLocationName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Event whereLongitude($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Event whereMaxAttendees($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Event wherePrice($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Event whereReminderSent($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Event whereStartsAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Event whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Event whereTitle($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Event whereTokenCost($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Event whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Event whereUpdatedAt($value)
 *
 * @mixin \Eloquent
 */
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
        'reminder_sent',
        'chatroom_id',
    ];

    protected $casts = [
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'latitude' => 'float',
        'longitude' => 'float',
        'price' => 'decimal:2',
        'token_cost' => 'decimal:2',
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

    public function groups()
    {
        return $this->belongsToMany(Group::class, 'event_groups')
            ->withTimestamps();
    }

    public function chatroom()
    {
        return $this->belongsTo(Chatroom::class);
    }
}
