<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Model;

class Topic extends Model
{
    protected $fillable = [
        'slug',
        'label',
        'description',
        'emoji',
        'category',
        'aliases',
        'is_featured',
        'sort_order',
    ];

    protected $casts = [
        'aliases' => 'array',
        'is_featured' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function followers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'topic_user_follows')
            ->withPivot('followed_at')
            ->withTimestamps();
    }

    public function matchesTokens(array $values): bool
    {
        $candidateTokens = static::normalizeTerms($values);
        $topicTokens = static::normalizeTerms([
            $this->slug,
            $this->label,
            ...($this->aliases ?? []),
        ]);

        return count(array_intersect($topicTokens, $candidateTokens)) > 0;
    }

    /**
     * @param  array<int, mixed>  $values
     * @return array<int, string>
     */
    public static function normalizeTerms(array $values): array
    {
        $normalized = array_map(function ($value) {
            if (! is_string($value)) {
                return null;
            }

            $clean = preg_replace('/\s+/', ' ', trim($value));
            if ($clean === null || $clean === '') {
                return null;
            }

            return mb_strtolower($clean);
        }, $values);

        return array_values(array_unique(array_filter($normalized)));
    }
}
