<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $user_id
 * @property string $type
 * @property bool $mail
 * @property bool $push
 * @property bool $database
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\User $user
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NotificationPreference newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NotificationPreference newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NotificationPreference query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NotificationPreference whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NotificationPreference whereDatabase($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NotificationPreference whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NotificationPreference whereMail($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NotificationPreference wherePush($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NotificationPreference whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NotificationPreference whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NotificationPreference whereUserId($value)
 * @mixin \Eloquent
 */
class NotificationPreference extends Model
{
    protected $fillable = [
        'user_id',
        'type',
        'mail',
        'push',
        'database',
    ];

    protected $casts = [
        'mail' => 'boolean',
        'push' => 'boolean',
        'database' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
