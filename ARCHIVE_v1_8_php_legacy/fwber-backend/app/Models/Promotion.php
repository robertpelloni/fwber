<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $merchant_id
 * @property string $title
 * @property string $description
 * @property string|null $promo_code
 * @property string $discount_value
 * @property numeric $lat
 * @property numeric $lng
 * @property int $radius
 * @property int $token_cost
 * @property \Illuminate\Support\Carbon $starts_at
 * @property \Illuminate\Support\Carbon $expires_at
 * @property bool $is_active
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property int $views
 * @property int $clicks
 * @property int $redemptions
 * @property-read \App\Models\MerchantProfile $merchantProfile
 *
 * @method static \Database\Factories\PromotionFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Promotion newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Promotion newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Promotion query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Promotion whereClicks($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Promotion whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Promotion whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Promotion whereDiscountValue($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Promotion whereExpiresAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Promotion whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Promotion whereIsActive($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Promotion whereLat($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Promotion whereLng($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Promotion whereMerchantId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Promotion wherePromoCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Promotion whereRadius($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Promotion whereRedemptions($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Promotion whereStartsAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Promotion whereTitle($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Promotion whereTokenCost($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Promotion whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Promotion whereViews($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Promotion withinBox(float $lat, float $lng, float $radiusMeters)
 *
 * @mixin \Eloquent
 */
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
        'views',
        'clicks',
        'redemptions',
    ];

    protected $casts = [
        'lat' => 'decimal:8',
        'lng' => 'decimal:8',
        'starts_at' => 'datetime',
        'expires_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    public function merchant()
    {
        return $this->belongsTo(MerchantProfile::class, 'merchant_id');
    }

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
