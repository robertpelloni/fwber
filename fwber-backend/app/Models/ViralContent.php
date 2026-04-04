<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

/**
 * Lightweight shareable AI output record used by restored Wingman/roast flows.
 *
 * This model was archived during the simplification, but the share pages and AI
 * helpers still understand `share_id` links. Restoring it lets us bring back
 * roast/hype/vibe/fortune sharing without reintroducing federation/governance.
 */
class ViralContent extends Model
{
    use HasUuids;

    protected $fillable = [
        'user_id',
        'type',
        'content',
        'views',
        'reward_claimed',
    ];

    protected $casts = [
        'content' => 'array',
        'views' => 'integer',
        'reward_claimed' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
