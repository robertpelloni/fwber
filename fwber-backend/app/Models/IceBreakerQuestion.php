<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $question
 * @property string $category
 * @property string $emoji
 * @property bool $is_active
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\IceBreakerAnswer> $answers
 * @property-read int|null $answers_count
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|IceBreakerQuestion active()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|IceBreakerQuestion byCategory(string $category)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|IceBreakerQuestion newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|IceBreakerQuestion newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|IceBreakerQuestion query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|IceBreakerQuestion whereCategory($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|IceBreakerQuestion whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|IceBreakerQuestion whereEmoji($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|IceBreakerQuestion whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|IceBreakerQuestion whereIsActive($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|IceBreakerQuestion whereQuestion($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|IceBreakerQuestion whereUpdatedAt($value)
 *
 * @mixin \Eloquent
 */
class IceBreakerQuestion extends Model
{
    use HasFactory;

    protected $fillable = [
        'question',
        'category',
        'emoji',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function answers(): HasMany
    {
        return $this->hasMany(IceBreakerAnswer::class, 'question_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByCategory($query, string $category)
    {
        return $query->where('category', $category);
    }
}
