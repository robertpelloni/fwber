<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PhotoReveal extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'match_id',
        'photo_id',
        'status',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function match()
    {
        return $this->belongsTo(UserMatch::class, 'match_id');
    }

    public function photo()
    {
        return $this->belongsTo(Photo::class);
    }
}
