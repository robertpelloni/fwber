<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DailyActiveUser extends Model
{
    protected $fillable = [
        'user_id',
        'date',
    ];

    protected $casts = [
        'date' => 'date',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
