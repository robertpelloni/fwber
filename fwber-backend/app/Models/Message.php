<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property string|null $uuid
 * @property int $sender_id
 * @property int $receiver_id
 * @property string|null $content
 * @property bool $is_encrypted
 * @property string $message_type
 * @property string|null $media_url
 * @property string|null $media_type
 * @property int|null $media_duration
 * @property string|null $transcription
 * @property bool $is_flagged
 * @property string|null $flagged_reason
 * @property string|null $thumbnail_url
 * @property bool $is_read
 * @property \Illuminate\Support\Carbon|null $sent_at
 * @property \Illuminate\Support\Carbon|null $read_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\User $receiver
 * @property-read \App\Models\User $sender
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Message between(int $userId1, int $userId2)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Message newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Message newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Message query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Message recent(int $limit = 50)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Message unread()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Message whereContent($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Message whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Message whereFlaggedReason($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Message whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Message whereIsEncrypted($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Message whereIsFlagged($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Message whereIsRead($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Message whereMediaDuration($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Message whereMediaType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Message whereMediaUrl($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Message whereMessageType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Message whereReadAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Message whereReceiverId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Message whereSenderId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Message whereSentAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Message whereThumbnailUrl($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Message whereTranscription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Message whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Message whereUuid($value)
 * @mixin \Eloquent
 */
class Message extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid',
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
        'uuid' => 'string',
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
