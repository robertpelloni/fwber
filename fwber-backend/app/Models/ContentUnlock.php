<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Compact ledger for token-spend unlocks.
 *
 * The simplified product no longer needs a sprawling unlock economy, but we do
 * need a durable record for the token-gated surfaces that still exist in the
 * frontend: match insights, private photos, and any future compact paywalled
 * content blocks.
 */
class ContentUnlock extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'content_type',
        'content_id',
        'cost',
    ];

    protected $casts = [
        'cost' => 'decimal:2',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
