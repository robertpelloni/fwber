<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $group_id
 * @property int $actor_user_id
 * @property int $target_user_id
 * @property string $action
 * @property string|null $reason
 * @property array<array-key, mixed>|null $metadata
 * @property \Illuminate\Support\Carbon $occurred_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Group $group
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GroupModerationEvent newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GroupModerationEvent newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GroupModerationEvent query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GroupModerationEvent whereAction($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GroupModerationEvent whereActorUserId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GroupModerationEvent whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GroupModerationEvent whereGroupId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GroupModerationEvent whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GroupModerationEvent whereMetadata($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GroupModerationEvent whereOccurredAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GroupModerationEvent whereReason($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GroupModerationEvent whereTargetUserId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GroupModerationEvent whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class GroupModerationEvent extends Model
{
    protected $fillable = [
        'group_id',
        'actor_user_id',
        'target_user_id',
        'action',
        'reason',
        'metadata',
        'occurred_at',
    ];

    protected $casts = [
        'metadata' => 'array',
        'occurred_at' => 'datetime',
    ];

    public function group(): BelongsTo
    {
        return $this->belongsTo(Group::class);
    }
}
