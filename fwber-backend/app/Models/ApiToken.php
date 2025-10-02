<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class ApiToken extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'token',
    ];

    protected $casts = [
        'last_used_at' => 'datetime',
    ];

    public static function generateForUser(User $user, ?string $name = null): string
    {
        $plain = Str::random(80);
        $hashed = hash('sha256', $plain);

        $user->tokens()->create([
            'name' => $name,
            'token' => $hashed,
        ]);

        return $plain;
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
