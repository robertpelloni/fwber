<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property-read \App\Models\GroupMessage|null $message
 * @property-read \App\Models\User|null $user
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GroupMessageRead newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GroupMessageRead newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GroupMessageRead query()
 *
 * @mixin \Eloquent
 */
class GroupMessageRead extends Model
{
    protected $fillable = [
        'group_message_id',
        'user_id',
        'read_at',
    ];

    protected $casts = [
        'read_at' => 'datetime',
    ];

    public function message(): BelongsTo
    {
        return $this->belongsTo(GroupMessage::class, 'group_message_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
