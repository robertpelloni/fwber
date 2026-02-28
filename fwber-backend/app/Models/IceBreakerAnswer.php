<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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
