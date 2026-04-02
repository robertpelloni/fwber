<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RelationshipLink extends Model
{
    use HasFactory;

    public const TYPE_DATING = 'dating';

    public const TYPE_PARTNER = 'partner';

    public const TYPE_SPOUSE = 'spouse';

    public const TYPE_OTHER = 'other';

    public const TYPE_OPTIONS = [
        self::TYPE_DATING,
        self::TYPE_PARTNER,
        self::TYPE_SPOUSE,
        self::TYPE_OTHER,
    ];

    public const VISIBILITY_PUBLIC = 'public';

    public const VISIBILITY_FRIENDS = 'friends';

    public const VISIBILITY_PRIVATE = 'private';

    public const VISIBILITY_OPTIONS = [
        self::VISIBILITY_PUBLIC,
        self::VISIBILITY_FRIENDS,
        self::VISIBILITY_PRIVATE,
    ];

    protected $fillable = [
        'user_id',
        'related_user_id',
        'relationship_type',
        'visibility',
        'note',
        'requested_at',
        'confirmed_at',
    ];

    protected $casts = [
        'requested_at' => 'datetime',
        'confirmed_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function relatedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'related_user_id');
    }

    public function counterpartFor(int $ownerId): ?User
    {
        if ($this->user_id === $ownerId) {
            return $this->relatedUser;
        }

        if ($this->related_user_id === $ownerId) {
            return $this->user;
        }

        return null;
    }

    public function involvesUser(int $userId): bool
    {
        return $this->user_id === $userId || $this->related_user_id === $userId;
    }

    public function isConfirmed(): bool
    {
        return $this->confirmed_at !== null;
    }
}
