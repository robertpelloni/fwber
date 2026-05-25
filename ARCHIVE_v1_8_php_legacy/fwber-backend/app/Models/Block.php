<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $blocker_id
 * @property int $blocked_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Block newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Block newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Block query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Block whereBlockedId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Block whereBlockerId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Block whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Block whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Block whereUpdatedAt($value)
 *
 * @mixin \Eloquent
 */
class Block extends Model
{
    use HasFactory;

    protected $fillable = ['blocker_id', 'blocked_id'];

    public static function isBlockedBetween(int $a, int $b): bool
    {
        return self::where(function ($q) use ($a, $b) {
            $q->where('blocker_id', $a)->where('blocked_id', $b);
        })->orWhere(function ($q) use ($a, $b) {
            $q->where('blocker_id', $b)->where('blocked_id', $a);
        })->exists();
    }
}
