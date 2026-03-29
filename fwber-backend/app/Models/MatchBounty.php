<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property int $user_id
 * @property string $slug
 * @property int $token_reward
 * @property string|null $description
 * @property string $status
 * @property \Illuminate\Support\Carbon|null $expires_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read \App\Models\User $user
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MatchBounty newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MatchBounty newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MatchBounty onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MatchBounty query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MatchBounty whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MatchBounty whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MatchBounty whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MatchBounty whereExpiresAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MatchBounty whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MatchBounty whereSlug($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MatchBounty whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MatchBounty whereTokenReward($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MatchBounty whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MatchBounty whereUserId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MatchBounty withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MatchBounty withoutTrashed()
 *
 * @mixin \Eloquent
 */
class MatchBounty extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'slug',
        'token_reward',
        'status',
        'description',
        'expires_at',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'token_reward' => 'integer',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
