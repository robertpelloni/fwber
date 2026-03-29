<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int|null $user_id
 * @property string $session_id
 * @property string $event_name
 * @property array<array-key, mixed>|null $payload
 * @property string|null $url
 * @property string|null $ip_address
 * @property string|null $user_agent
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\User|null $user
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ClickstreamEvent newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ClickstreamEvent newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ClickstreamEvent query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ClickstreamEvent whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ClickstreamEvent whereEventName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ClickstreamEvent whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ClickstreamEvent whereIpAddress($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ClickstreamEvent wherePayload($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ClickstreamEvent whereSessionId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ClickstreamEvent whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ClickstreamEvent whereUrl($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ClickstreamEvent whereUserAgent($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ClickstreamEvent whereUserId($value)
 * @mixin \Eloquent
 */
class ClickstreamEvent extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'session_id',
        'event_name',
        'payload',
        'url',
        'ip_address',
        'user_agent',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'payload' => 'array',
    ];

    /**
     * Get the user that owns the event.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
