<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $user_id
 * @property numeric $amount
 * @property string $type
 * @property string|null $description
 * @property array<array-key, mixed>|null $metadata
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\User $user
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TokenTransaction newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TokenTransaction newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TokenTransaction query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TokenTransaction whereAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TokenTransaction whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TokenTransaction whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TokenTransaction whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TokenTransaction whereMetadata($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TokenTransaction whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TokenTransaction whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TokenTransaction whereUserId($value)
 * @mixin \Eloquent
 */
class TokenTransaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'amount',
        'type',
        'description',
        'metadata',
    ];

    protected $casts = [
        'amount' => 'decimal:4',
        'metadata' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
