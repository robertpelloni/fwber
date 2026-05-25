<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $group_id
 * @property int $user_id
 * @property string $content
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Group $group
 * @property-read \App\Models\User $user
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GroupPost newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GroupPost newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GroupPost query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GroupPost whereContent($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GroupPost whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GroupPost whereGroupId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GroupPost whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GroupPost whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GroupPost whereUserId($value)
 *
 * @mixin \Eloquent
 */
class GroupPost extends Model
{
    protected $fillable = [
        'group_id',
        'user_id',
        'content',
    ];

    public function group(): BelongsTo
    {
        return $this->belongsTo(Group::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
