<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ScrapbookEntry extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'match_user_id',
        'type',
        'content',
        'media_url',
        'media_type',
        'emoji',
        'color',
        'is_pinned',
    ];

    protected $casts = [
        'is_pinned' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function matchUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'match_user_id');
    }

    /**
     * Scope entries for a specific match pair (both directions)
     */
    public function scopeForPair($query, int $userId, int $matchId)
    {
        return $query->where(function ($q) use ($userId, $matchId) {
            $q->where('user_id', $userId)->where('match_user_id', $matchId);
        })->orWhere(function ($q) use ($userId, $matchId) {
            $q->where('user_id', $matchId)->where('match_user_id', $userId);
        });
    }

    public function scopePinned($query)
    {
        return $query->where('is_pinned', true);
    }
}
