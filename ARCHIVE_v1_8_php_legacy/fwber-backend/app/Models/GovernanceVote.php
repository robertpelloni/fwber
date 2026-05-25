<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GovernanceVote extends Model
{
    protected $fillable = [
        'user_id',
        'governance_proposal_id',
        'option_index',
        'token_weight',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function proposal(): BelongsTo
    {
        return $this->belongsTo(GovernanceProposal::class, 'governance_proposal_id');
    }
}
