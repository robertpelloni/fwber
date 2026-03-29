<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property-read \App\Models\User|null $user
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserPhoto newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserPhoto newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserPhoto query()
 * @mixin \Eloquent
 */
class UserPhoto extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'url',
        'is_primary',
        'caption',
    ];

    protected $casts = [
        'is_primary' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
