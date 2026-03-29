<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * @property int $id
 * @property int $user1_id
 * @property int $user2_id
 * @property bool $is_active
 * @property \Illuminate\Support\Carbon|null $last_message_at
 * @property string $status
 * @property int|null $match_score
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Message> $messages
 * @property-read int|null $messages_count
 * @property-read \App\Models\RelationshipTier|null $relationshipTier
 * @property-read \App\Models\User $user1
 * @property-read \App\Models\User $user2
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserMatch newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserMatch newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserMatch query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserMatch whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserMatch whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserMatch whereIsActive($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserMatch whereLastMessageAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserMatch whereMatchScore($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserMatch whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserMatch whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserMatch whereUser1Id($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserMatch whereUser2Id($value)
 * @mixin \Eloquent
 */
class UserMatch extends Model
{
    protected $table = 'matches';

    protected $fillable = [
        'user1_id',
        'user2_id',
        'is_active',
        'last_message_at',
        'status',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'last_message_at' => 'datetime',
    ];

    public function user1(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user1_id');
    }

    public function user2(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user2_id');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class, 'match_id');
    }

    public function relationshipTier(): HasOne
    {
        return $this->hasOne(RelationshipTier::class, 'match_id');
    }

    /**
     * Get the other user in the match
     */
    public function getOtherUser(int $currentUserId): ?User
    {
        if ($this->user1_id === $currentUserId) {
            return $this->user2;
        }

        if ($this->user2_id === $currentUserId) {
            return $this->user1;
        }

        return null;
    }
}
