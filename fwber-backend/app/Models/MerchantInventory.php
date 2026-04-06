<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MerchantInventory extends Model
{
    protected $fillable = [
        'merchant_profile_id',
        'name',
        'description',
        'price_tokens',
        'stock_count',
        'image_url',
        'is_available',
    ];

    public function merchant(): BelongsTo
    {
        return $this->belongsTo(MerchantProfile::class, 'merchant_profile_id');
    }

    public function redemptions(): HasMany
    {
        return $this->hasMany(InventoryRedemption::class);
    }
}
