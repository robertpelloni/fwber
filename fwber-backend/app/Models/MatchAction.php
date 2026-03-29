<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $user_id
 * @property int $target_user_id
 * @property string $action
 * @property array<array-key, mixed>|null $metadata
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\User $targetUser
 * @property-read \App\Models\User $user
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MatchAction newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MatchAction newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MatchAction query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MatchAction whereAction($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MatchAction whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MatchAction whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MatchAction whereMetadata($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MatchAction whereTargetUserId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MatchAction whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MatchAction whereUserId($value)
 * @mixin \Eloquent
 */
class MatchAction extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'target_user_id',
        'action', // like, pass, super_like
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function targetUser()
    {
        return $this->belongsTo(User::class, 'target_user_id');
    }
}
