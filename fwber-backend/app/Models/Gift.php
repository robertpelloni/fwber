<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Gift extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'cost',
        'icon_url',
        'category',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'cost' => 'integer',
    ];
}
