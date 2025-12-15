<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Achievement extends Model
{
    protected $fillable = [
        'name',
        'description',
        'icon',
        'category',
        'reward_tokens',
        'criteria_type',
        'criteria_value',
        'is_hidden',
    ];

    protected $casts = [
        'is_hidden' => 'boolean',
        'reward_tokens' => 'integer',
        'criteria_value' => 'integer',
    ];

    public function users()
    {
        return $this->belongsToMany(User::class, 'user_achievements')
            ->withPivot('unlocked_at')
            ->withTimestamps();
    }
}
