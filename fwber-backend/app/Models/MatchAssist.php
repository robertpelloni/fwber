<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int|null $match_bounty_id
 * @property int $matchmaker_id
 * @property int $subject_id
 * @property int $target_id
 * @property string $status
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\MatchBounty|null $bounty
 * @property-read \App\Models\User $matchmaker
 * @property-read \App\Models\User $subject
 * @property-read \App\Models\User $target
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MatchAssist newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MatchAssist newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MatchAssist query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MatchAssist whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MatchAssist whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MatchAssist whereMatchBountyId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MatchAssist whereMatchmakerId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MatchAssist whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MatchAssist whereSubjectId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MatchAssist whereTargetId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MatchAssist whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class MatchAssist extends Model
{
    protected $fillable = [
        'match_bounty_id',
        'matchmaker_id',
        'subject_id',
        'target_id',
        'status',
    ];

    public function matchmaker()
    {
        return $this->belongsTo(User::class, 'matchmaker_id');
    }

    public function subject()
    {
        return $this->belongsTo(User::class, 'subject_id');
    }

    public function target()
    {
        return $this->belongsTo(User::class, 'target_id');
    }

    public function bounty()
    {
        return $this->belongsTo(MatchBounty::class, 'match_bounty_id');
    }
}
