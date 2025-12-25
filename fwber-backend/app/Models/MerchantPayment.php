<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class MerchantPayment extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'merchant_id',
        'payer_id',
        'amount',
        'status',
        'description',
        'redirect_url',
        'webhook_url',
        'metadata',
        'paid_at',
    ];

    protected $casts = [
        'amount' => 'decimal:4',
        'paid_at' => 'datetime',
        'metadata' => 'array',
    ];

    public function merchant()
    {
        return $this->belongsTo(User::class, 'merchant_id');
    }

    public function payer()
    {
        return $this->belongsTo(User::class, 'payer_id');
    }
}
