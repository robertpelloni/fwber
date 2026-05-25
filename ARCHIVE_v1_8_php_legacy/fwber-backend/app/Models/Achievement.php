<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $name
 * @property string $description
 * @property string $icon
 * @property string $category
 * @property int $reward_tokens
 * @property string $criteria_type
 * @property int $criteria_value
 * @property bool $is_hidden
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\User> $users
 * @property-read int|null $users_count
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Achievement newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Achievement newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Achievement query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Achievement whereCategory($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Achievement whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Achievement whereCriteriaType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Achievement whereCriteriaValue($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Achievement whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Achievement whereIcon($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Achievement whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Achievement whereIsHidden($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Achievement whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Achievement whereRewardTokens($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Achievement whereUpdatedAt($value)
 *
 * @mixin \Eloquent
 */
class Achievement extends Model
{
    protected $fillable = [
        'name',
        'description',
        'icon',
        'category',
        'reward_tokens',
        'criteria_type',
        'criteria_value',
        'is_hidden',
    ];

    protected $casts = [
        'is_hidden' => 'boolean',
        'reward_tokens' => 'integer',
        'criteria_value' => 'integer',
    ];

    public function users()
    {
        return $this->belongsToMany(User::class, 'user_achievements')
            ->withPivot('unlocked_at')
            ->withTimestamps();
    }
}
