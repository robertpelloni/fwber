<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $user_id
 * @property int $match_id
 * @property int $photo_id
 * @property string $status
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\UserMatch $match
 * @property-read \App\Models\Photo $photo
 * @property-read \App\Models\User $user
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PhotoReveal newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PhotoReveal newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PhotoReveal query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PhotoReveal whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PhotoReveal whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PhotoReveal whereMatchId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PhotoReveal wherePhotoId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PhotoReveal whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PhotoReveal whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PhotoReveal whereUserId($value)
 *
 * @mixin \Eloquent
 */
class PhotoReveal extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'match_id',
        'photo_id',
        'status',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function match()
    {
        return $this->belongsTo(UserMatch::class, 'match_id');
    }

    public function photo()
    {
        return $this->belongsTo(Photo::class);
    }
}
