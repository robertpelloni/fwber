<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Journal extends Model
{
    use HasFactory;

    public const VISIBILITY_PUBLIC = 'public';

    public const VISIBILITY_FRIENDS = 'friends';

    public const VISIBILITY_CIRCLE = 'circle';

    public const VISIBILITY_PRIVATE = 'private';

    public const VISIBILITY_OPTIONS = [
        self::VISIBILITY_PUBLIC,
        self::VISIBILITY_FRIENDS,
        self::VISIBILITY_CIRCLE,
        self::VISIBILITY_PRIVATE,
    ];

    protected $fillable = [
        'user_id',
        'title',
        'content',
        'visibility',
        'circle_group_id',
        'tags',
        'mood_emoji',
        'accent_color',
    ];

    protected $casts = [
        'tags' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function circleGroup(): BelongsTo
    {
        return $this->belongsTo(Group::class, 'circle_group_id');
    }
}
