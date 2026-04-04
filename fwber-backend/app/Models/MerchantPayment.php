<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Marketplace payment ledger entry.
 *
 * This mirrors the compact premium billing restoration pattern: enough detail
 * for merchant analytics, purchase verification, and support debugging without
 * reviving the full historic payments stack.
 */
class MerchantPayment extends Model
{
    protected $fillable = [
        'merchant_profile_id',
        'merchant_inventory_id',
        'payer_id',
        'amount',
        'currency',
        'payment_gateway',
        'transaction_id',
        'status',
        'description',
        'metadata',
        'paid_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'metadata' => 'array',
        'paid_at' => 'datetime',
    ];

    public function merchant()
    {
        return $this->belongsTo(MerchantProfile::class, 'merchant_profile_id');
    }

    public function inventory()
    {
        return $this->belongsTo(MerchantInventory::class, 'merchant_inventory_id');
    }

    public function payer()
    {
        return $this->belongsTo(User::class, 'payer_id');
    }
}
