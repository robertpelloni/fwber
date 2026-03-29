<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $user_id
 * @property int $photo_id
 * @property numeric $cost
 * @property string $unlocked_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Photo $photo
 * @property-read \App\Models\User $user
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PhotoUnlock newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PhotoUnlock newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PhotoUnlock query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PhotoUnlock whereCost($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PhotoUnlock whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PhotoUnlock whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PhotoUnlock wherePhotoId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PhotoUnlock whereUnlockedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PhotoUnlock whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PhotoUnlock whereUserId($value)
 * @mixin \Eloquent
 */
class PhotoUnlock extends Model
{
    protected $fillable = ['user_id', 'photo_id', 'cost', 'unlocked_at'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function photo(): BelongsTo
    {
        return $this->belongsTo(Photo::class);
    }
}
