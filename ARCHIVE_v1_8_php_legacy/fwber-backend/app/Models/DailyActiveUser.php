<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $user_id
 * @property \Illuminate\Support\Carbon $date
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\User $user
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DailyActiveUser newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DailyActiveUser newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DailyActiveUser query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DailyActiveUser whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DailyActiveUser whereDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DailyActiveUser whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DailyActiveUser whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DailyActiveUser whereUserId($value)
 *
 * @mixin \Eloquent
 */
class DailyActiveUser extends Model
{
    protected $fillable = [
        'user_id',
        'date',
    ];

    protected $casts = [
        'date' => 'date',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
