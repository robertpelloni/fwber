<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

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
