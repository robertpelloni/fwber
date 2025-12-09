<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GroupMessage extends Model
{
    protected $fillable = [
        'group_id',
        'sender_id',
        'content',
        'message_type',
        'media_url',
        'media_type',
        'media_duration',
        'thumbnail_url',
        'sent_at',
        'is_deleted',
    ];

    protected $casts = [
        'sent_at' => 'datetime',
        'is_deleted' => 'boolean',
    ];

    public function group(): BelongsTo
    {
        return $this->belongsTo(Group::class);
    }

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    public function reads(): HasMany
    {
        return $this->hasMany(GroupMessageRead::class);
    }

    public function readCount(): int
    {
        return $this->reads()->count();
    }

    public function isReadBy(int $userId): bool
    {
        return $this->reads()->where('user_id', $userId)->exists();
    }

    public function scopeNotDeleted($query)
    {
        return $query->where('is_deleted', false);
    }
}
