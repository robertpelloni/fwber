<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class E2EKeyBackup extends Model
{
    protected $table = 'e2e_key_backups';

    protected $fillable = [
        'user_id',
        'key_type',
        'encrypted_private_key',
        'salt',
        'iv',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
