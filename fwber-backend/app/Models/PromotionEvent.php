<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $promotion_id
 * @property int|null $user_id
 * @property string $type
 * @property array<array-key, mixed>|null $metadata
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Promotion $promotion
 * @property-read \App\Models\User|null $user
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PromotionEvent newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PromotionEvent newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PromotionEvent query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PromotionEvent whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PromotionEvent whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PromotionEvent whereMetadata($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PromotionEvent wherePromotionId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PromotionEvent whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PromotionEvent whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PromotionEvent whereUserId($value)
 * @mixin \Eloquent
 */
class PromotionEvent extends Model
{
    use HasFactory;

    protected $fillable = [
        'promotion_id',
        'user_id',
        'type',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
    ];

    public function promotion()
    {
        return $this->belongsTo(Promotion::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
