<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $user_id
 * @property int $friend_id
 * @property string $status
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\User $friend
 * @property-read \App\Models\User $user
 * @method static \Database\Factories\FriendFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Friend newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Friend newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Friend query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Friend whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Friend whereFriendId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Friend whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Friend whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Friend whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Friend whereUserId($value)
 * @mixin \Eloquent
 */
class Friend extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'friend_id',
        'status',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function friend(): BelongsTo
    {
        return $this->belongsTo(User::class, 'friend_id');
    }
}
