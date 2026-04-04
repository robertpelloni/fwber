<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Restored merchant inventory item.
 *
 * Items are intentionally simple physical/digital goods tracked by merchant,
 * price, availability, and stock. This is the minimum viable commerce record
 * needed for storefront browsing, mock purchases, and redemption issuance.
 */
class MerchantInventory extends Model
{
    protected $fillable = [
        'merchant_profile_id',
        'name',
        'description',
        'price_usd',
        'stock_count',
        'image_url',
        'is_available',
    ];

    protected $casts = [
        'price_usd' => 'decimal:2',
        'stock_count' => 'integer',
        'is_available' => 'boolean',
    ];

    public function merchant()
    {
        return $this->belongsTo(MerchantProfile::class, 'merchant_profile_id');
    }

    public function redemptions()
    {
        return $this->hasMany(InventoryRedemption::class);
    }

    public function payments()
    {
        return $this->hasMany(MerchantPayment::class, 'merchant_inventory_id');
    }
}
