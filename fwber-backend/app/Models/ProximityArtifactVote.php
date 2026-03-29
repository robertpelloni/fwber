<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property string $id
 * @property int $proximity_artifact_id
 * @property int $user_id
 * @property int $value
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\ProximityArtifact|null $artifact
 * @property-read \App\Models\User $user
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProximityArtifactVote newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProximityArtifactVote newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProximityArtifactVote query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProximityArtifactVote whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProximityArtifactVote whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProximityArtifactVote whereProximityArtifactId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProximityArtifactVote whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProximityArtifactVote whereUserId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProximityArtifactVote whereValue($value)
 *
 * @mixin \Eloquent
 */
class ProximityArtifactVote extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'proximity_artifact_id',
        'user_id',
        'value',
    ];

    public function artifact(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(ProximityArtifact::class, 'proximity_artifact_id');
    }

    public function user(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
