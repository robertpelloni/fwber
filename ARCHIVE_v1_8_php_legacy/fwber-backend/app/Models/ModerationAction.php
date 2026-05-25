<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $moderator_id
 * @property int|null $target_user_id
 * @property int|null $target_artifact_id
 * @property string $action_type
 * @property string $reason
 * @property array<array-key, mixed>|null $metadata
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\User $moderator
 * @property-read \App\Models\ProximityArtifact|null $targetArtifact
 * @property-read \App\Models\User|null $targetUser
 *
 * @method static \Database\Factories\ModerationActionFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ModerationAction newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ModerationAction newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ModerationAction query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ModerationAction whereActionType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ModerationAction whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ModerationAction whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ModerationAction whereMetadata($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ModerationAction whereModeratorId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ModerationAction whereReason($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ModerationAction whereTargetArtifactId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ModerationAction whereTargetUserId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ModerationAction whereUpdatedAt($value)
 *
 * @mixin \Eloquent
 */
class ModerationAction extends Model
{
    use HasFactory;

    protected $fillable = [
        'moderator_id',
        'target_user_id',
        'target_artifact_id',
        'action_type',
        'reason',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
    ];

    /**
     * Get the moderator who performed this action.
     */
    public function moderator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'moderator_id');
    }

    /**
     * Get the target user (if applicable).
     */
    public function targetUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'target_user_id');
    }

    /**
     * Get the target artifact (if applicable).
     */
    public function targetArtifact(): BelongsTo
    {
        return $this->belongsTo(ProximityArtifact::class, 'target_artifact_id');
    }
}
