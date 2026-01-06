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

    public function scopeWithinBox($query, float $lat, float $lng, float $radiusMeters)
    {
        // Approximate: 1 deg lat ~ 111,000 m; 1 deg lng ~ 111,000 m * cos(lat)
        $latOffset = $radiusMeters / 111000.0;
        $lngOffset = $radiusMeters / (111000.0 * max(0.1, cos(deg2rad($lat))));
        return $query->whereBetween('lat', [$lat - $latOffset, $lat + $latOffset])
                     ->whereBetween('lng', [$lng - $lngOffset, $lng + $lngOffset]);
    }
}
