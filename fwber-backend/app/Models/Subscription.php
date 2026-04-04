<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Minimal subscription record restored for premium plan lifecycle visibility.
 *
 * We only restore the fields needed for current status, expiry, and Stripe id
 * tracking. More elaborate creator/merchant subscription systems remain out of
 * scope for this phase.
 */
class Subscription extends Model
{
    protected $fillable = [
        'user_id',
        'name',
        'stripe_id',
        'stripe_status',
        'stripe_price',
        'quantity',
        'trial_ends_at',
        'ends_at',
    ];

    protected $casts = [
        'trial_ends_at' => 'datetime',
        'ends_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
