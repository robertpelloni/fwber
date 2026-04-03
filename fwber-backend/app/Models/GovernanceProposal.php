<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GovernanceProposal extends Model
{
    protected $fillable = [
        'creator_id',
        'title',
        'description',
        'category',
        'options',
        'execution_payload',
        'min_tokens_required',
        'starts_at',
        'expires_at',
        'status',
        'executed_at',
    ];

    protected $casts = [
        'options' => 'array',
        'execution_payload' => 'array',
        'starts_at' => 'datetime',
        'expires_at' => 'datetime',
        'executed_at' => 'datetime',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creator_id');
    }

    public function votes(): HasMany
    {
        return $this->hasMany(GovernanceVote::class);
    }
}
