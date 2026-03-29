<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FederatedPost newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FederatedPost newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FederatedPost query()
 *
 * @mixin \Eloquent
 */
class FederatedPost extends Model
{
    protected $fillable = [
        'guid',
        'actor_uri',
        'actor_username',
        'actor_domain',
        'actor_avatar',
        'content',
        'url',
        'metadata',
        'published_at',
    ];

    protected $casts = [
        'metadata' => 'array',
        'published_at' => 'datetime',
    ];
}
