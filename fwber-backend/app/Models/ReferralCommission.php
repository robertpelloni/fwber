<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReferralCommission extends Model
{
    protected $fillable = [
        'commission_key',
        'purchaser_user_id',
        'beneficiary_user_id',
        'payment_id',
        'subscription_id',
        'level',
        'cash_amount',
        'cash_currency',
        'cash_status',
        'token_amount',
        'source',
        'metadata',
    ];

    protected $casts = [
        'cash_amount' => 'decimal:2',
        'token_amount' => 'decimal:4',
        'metadata' => 'array',
    ];

    public function purchaser()
    {
        return $this->belongsTo(User::class, 'purchaser_user_id');
    }

    public function beneficiary()
    {
        return $this->belongsTo(User::class, 'beneficiary_user_id');
    }

    public function payment()
    {
        return $this->belongsTo(Payment::class);
    }

    public function subscription()
    {
        return $this->belongsTo(Subscription::class);
    }
}
