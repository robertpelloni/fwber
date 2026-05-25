<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property string $id
 * @property int $proximity_artifact_id
 * @property int $user_id
 * @property string $content
 * @property string|null $parent_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read \App\Models\ProximityArtifact|null $artifact
 * @property-read ProximityArtifactComment|null $parent
 * @property-read \Illuminate\Database\Eloquent\Collection<int, ProximityArtifactComment> $replies
 * @property-read int|null $replies_count
 * @property-read \App\Models\User $user
 *
 * @method static \Database\Factories\ProximityArtifactCommentFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProximityArtifactComment newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProximityArtifactComment newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProximityArtifactComment onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProximityArtifactComment query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProximityArtifactComment whereContent($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProximityArtifactComment whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProximityArtifactComment whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProximityArtifactComment whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProximityArtifactComment whereParentId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProximityArtifactComment whereProximityArtifactId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProximityArtifactComment whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProximityArtifactComment whereUserId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProximityArtifactComment withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProximityArtifactComment withoutTrashed()
 *
 * @mixin \Eloquent
 */
class ProximityArtifactComment extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $fillable = [
        'proximity_artifact_id',
        'user_id',
        'content',
        'parent_id',
    ];

    public function artifact()
    {
        return $this->belongsTo(ProximityArtifact::class, 'proximity_artifact_id');
    }

    public function user(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function parent(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function replies(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }
}
