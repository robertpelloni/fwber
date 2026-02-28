<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class IceBreakerQuestion extends Model
{
    use HasFactory;

    protected $fillable = [
        'question',
        'category',
        'emoji',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function answers(): HasMany
    {
        return $this->hasMany(IceBreakerAnswer::class, 'question_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByCategory($query, string $category)
    {
        return $query->where('category', $category);
    }
}
