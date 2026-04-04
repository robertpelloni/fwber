<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Lightweight payment ledger for restored premium purchases.
 *
 * The Great Simplification archived fiat billing, but the frontend still keeps
 * premium hooks, upgrade modals, and billing entry points. This model restores
 * just enough accounting to support premium status/history and future Stripe
 * reconciliation without reviving the full token economy.
 */
class Payment extends Model
{
    protected $fillable = [
        'user_id',
        'amount',
        'currency',
        'payment_gateway',
        'transaction_id',
        'status',
        'description',
        'metadata',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'metadata' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
