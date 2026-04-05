<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Compact referral commission ledger restored for the Hetzner-era rebuild.
 *
 * We intentionally keep the model small but explicit so referral payouts can be
 * tracked, premium purchase rewards can be audited, and the wallet surface can
 * show real pending cash instead of placeholder numbers.
 */
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
        'token_amount' => 'decimal:2',
        'metadata' => 'array',
    ];

    public function purchaser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'purchaser_user_id');
    }

    public function beneficiary(): BelongsTo
    {
        return $this->belongsTo(User::class, 'beneficiary_user_id');
    }

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class);
    }
}
