<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $user_id
 * @property int $match_user_id
 * @property string $type
 * @property string $content
 * @property string|null $media_url
 * @property string|null $media_type
 * @property string|null $emoji
 * @property string|null $color
 * @property bool $is_pinned
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\User $matchUser
 * @property-read \App\Models\User $user
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ScrapbookEntry forPair(int $userId, int $matchId)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ScrapbookEntry newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ScrapbookEntry newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ScrapbookEntry pinned()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ScrapbookEntry query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ScrapbookEntry whereColor($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ScrapbookEntry whereContent($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ScrapbookEntry whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ScrapbookEntry whereEmoji($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ScrapbookEntry whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ScrapbookEntry whereIsPinned($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ScrapbookEntry whereMatchUserId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ScrapbookEntry whereMediaType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ScrapbookEntry whereMediaUrl($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ScrapbookEntry whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ScrapbookEntry whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ScrapbookEntry whereUserId($value)
 * @mixin \Eloquent
 */
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
