<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class ViralContent extends Model
{
    use HasUuids;

    protected $fillable = [
        'user_id',
        'type',
        'content',
        'views',
        'reward_claimed',
    ];

    protected $casts = [
        'content' => 'array',
        'views' => 'integer',
        'reward_claimed' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
