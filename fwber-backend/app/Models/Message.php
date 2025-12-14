<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Message extends Model
{
    use HasFactory;

    protected $fillable = [
        'sender_id',
        'receiver_id',
        'content',
        'message_type',
        'media_url',
        'media_type',
        'media_duration',
        'thumbnail_url',
        'transcription',
        'is_flagged',
        'flagged_reason',
        'is_encrypted',
        'is_read',
        'sent_at',
        'read_at',
    ];

    protected $casts = [
        'sent_at' => 'datetime',
        'read_at' => 'datetime',
        'is_read' => 'boolean',
        'is_flagged' => 'boolean',
        'is_encrypted' => 'boolean',
        'media_duration' => 'integer',
    ];

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    public function receiver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'receiver_id');
    }

    public function scopeUnread($query)
    {
        return $query->where('is_read', false);
    }

    public function scopeBetween($query, int $userId1, int $userId2)
    {
        return $query->where(function ($q) use ($userId1, $userId2) {
            $q->where('sender_id', $userId1)->where('receiver_id', $userId2)
              ->orWhere('sender_id', $userId2)->where('receiver_id', $userId1);
        });
    }

    public function scopeRecent($query, int $limit = 50)
    {
        return $query->orderBy('sent_at', 'desc')->limit($limit);
    }
}
