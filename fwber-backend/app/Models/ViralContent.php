<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

/**
 * @property string $id
 * @property int $user_id
 * @property string $type
 * @property array<array-key, mixed> $content
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property int $views
 * @property bool $reward_claimed
 * @property-read \App\Models\User $user
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ViralContent newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ViralContent newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ViralContent query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ViralContent whereContent($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ViralContent whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ViralContent whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ViralContent whereRewardClaimed($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ViralContent whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ViralContent whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ViralContent whereUserId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ViralContent whereViews($value)
 *
 * @mixin \Eloquent
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
