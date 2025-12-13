<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ShareUnlock extends Model
{
    protected $fillable = [
        'user_id',
        'target_profile_id',
        'platform',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function targetProfile()
    {
        return $this->belongsTo(User::class, 'target_profile_id');
    }
}
