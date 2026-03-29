<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property-read \App\Models\User|null $user
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Following newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Following newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Following query()
 * @mixin \Eloquent
 */
class Following extends Model
{
    protected $fillable = [
        'user_id',
        'actor_uri',
        'username',
        'domain',
        'status',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
