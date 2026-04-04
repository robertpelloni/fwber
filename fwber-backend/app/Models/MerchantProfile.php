<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Compact merchant identity record restored for the marketplace phase.
 *
 * We keep merchant data intentionally lean: a merchant belongs to one user, can
 * expose inventory, and can accumulate payment/redemption analytics. This lets
 * us restore storefronts and local commerce without reviving the entire older
 * promotion economy in one risky sweep.
 */
class MerchantProfile extends Model
{
    protected $fillable = [
        'user_id',
        'business_name',
        'description',
        'category',
        'address',
        'verification_status',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function inventories()
    {
        return $this->hasMany(MerchantInventory::class);
    }

    public function payments()
    {
        return $this->hasMany(MerchantPayment::class);
    }
}
