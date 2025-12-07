<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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
