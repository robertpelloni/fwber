<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NotificationPreference extends Model
{
    protected $fillable = [
        'user_id',
        'type',
        'mail',
        'push',
        'database',
    ];

    protected $casts = [
        'mail' => 'boolean',
        'push' => 'boolean',
        'database' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
