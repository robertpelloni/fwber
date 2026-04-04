<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * A one-time redemption record issued after a successful marketplace purchase.
 *
 * The code can later be redeemed by the merchant in-person or at pickup. This
 * preserves a privacy-friendly proof of purchase without needing a complex POS
 * dependency for the initial restore.
 */
class InventoryRedemption extends Model
{
    protected $fillable = [
        'user_id',
        'merchant_inventory_id',
        'merchant_payment_id',
        'redemption_code',
        'redeemed_at',
    ];

    protected $casts = [
        'redeemed_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function inventory()
    {
        return $this->belongsTo(MerchantInventory::class, 'merchant_inventory_id');
    }

    public function payment()
    {
        return $this->belongsTo(MerchantPayment::class, 'merchant_payment_id');
    }
}
