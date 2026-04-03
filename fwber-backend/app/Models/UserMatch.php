<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $user1_id
 * @property int $user2_id
 * @property bool $is_active
 * @property int|null $match_score
 * @property bool $nfc_verified
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Message> $messages
 * @property-read int|null $messages_count
 * @property-read \App\Models\User $user1
 * @property-read \App\Models\User $user2
 */
class UserMatch extends Model
{
    protected $table = 'user_matches';

    protected $fillable = [
        'user1_id',
        'user2_id',
        'is_active',
        'match_score',
        'nfc_verified',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'nfc_verified' => 'boolean',
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
        return $this->hasMany(Message::class, 'receiver_id', 'user1_id'); // Simple relation or filtered query needed
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
