<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InventoryRedemption extends Model
{
    protected $fillable = [
        'user_id',
        'merchant_inventory_id',
        'redemption_code',
        'redeemed_at',
    ];

    protected $casts = [
        'redeemed_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function inventory(): BelongsTo
    {
        return $this->belongsTo(MerchantInventory::class, 'merchant_inventory_id');
    }
}
