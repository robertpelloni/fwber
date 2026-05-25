<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $user_id
 * @property string $actor_uri The ActivityPub URI of the remote follower
 * @property string|null $username
 * @property string|null $domain
 * @property string $status
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\User $user
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Follower newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Follower newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Follower query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Follower whereActorUri($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Follower whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Follower whereDomain($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Follower whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Follower whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Follower whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Follower whereUserId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Follower whereUsername($value)
 *
 * @mixin \Eloquent
 */
class Follower extends Model
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
