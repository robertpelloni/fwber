<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $user_id
 * @property string $target_entity_type
 * @property int $target_entity_id
 * @property string $proof_hash
 * @property bool $is_verified
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\User $user
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ZkProximityProof newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ZkProximityProof newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ZkProximityProof query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ZkProximityProof whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ZkProximityProof whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ZkProximityProof whereIsVerified($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ZkProximityProof whereProofHash($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ZkProximityProof whereTargetEntityId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ZkProximityProof whereTargetEntityType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ZkProximityProof whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ZkProximityProof whereUserId($value)
 * @mixin \Eloquent
 */
class ZkProximityProof extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'target_entity_type',
        'target_entity_id',
        'proof_hash',
        'is_verified',
    ];

    protected $casts = [
        'is_verified' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
