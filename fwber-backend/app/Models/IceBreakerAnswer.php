<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $user_id
 * @property int $question_id
 * @property int $match_user_id
 * @property string $answer
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\User $matchUser
 * @property-read \App\Models\IceBreakerQuestion $question
 * @property-read \App\Models\User $user
 * @method static \Illuminate\Database\Eloquent\Builder<static>|IceBreakerAnswer newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|IceBreakerAnswer newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|IceBreakerAnswer query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|IceBreakerAnswer whereAnswer($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|IceBreakerAnswer whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|IceBreakerAnswer whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|IceBreakerAnswer whereMatchUserId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|IceBreakerAnswer whereQuestionId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|IceBreakerAnswer whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|IceBreakerAnswer whereUserId($value)
 * @mixin \Eloquent
 */
class IceBreakerAnswer extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'question_id',
        'match_user_id',
        'answer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function matchUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'match_user_id');
    }

    public function question(): BelongsTo
    {
        return $this->belongsTo(IceBreakerQuestion::class, 'question_id');
    }

    /**
     * Check if the other user has also answered this question for this pair
     */
    public function isMutuallyAnswered(): bool
    {
        return static::where('question_id', $this->question_id)
            ->where('user_id', $this->match_user_id)
            ->where('match_user_id', $this->user_id)
            ->exists();
    }
}
