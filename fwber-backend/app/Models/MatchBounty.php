<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MatchBounty extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'slug',
        'token_reward',
        'status',
        'description',
        'expires_at',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'token_reward' => 'integer',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
