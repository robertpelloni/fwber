<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Promotion extends Model
{
    use HasFactory;

    protected $fillable = [
        'merchant_id',
        'title',
        'description',
        'promo_code',
        'discount_value',
        'lat',
        'lng',
        'radius',
        'token_cost',
        'starts_at',
        'expires_at',
        'is_active',
    ];

    protected $casts = [
        'lat' => 'decimal:8',
        'lng' => 'decimal:8',
        'starts_at' => 'datetime',
        'expires_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    public function merchantProfile()
    {
        return $this->belongsTo(MerchantProfile::class, 'merchant_id');
    }
}
