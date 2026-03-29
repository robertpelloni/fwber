<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $user_id
 * @property string $content_type
 * @property string $content_id
 * @property int $cost
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\User $user
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ContentUnlock newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ContentUnlock newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ContentUnlock query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ContentUnlock whereContentId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ContentUnlock whereContentType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ContentUnlock whereCost($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ContentUnlock whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ContentUnlock whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ContentUnlock whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ContentUnlock whereUserId($value)
 * @mixin \Eloquent
 */
class ContentUnlock extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'content_type',
        'content_id',
        'cost',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
