<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property string $id
 * @property int $merchant_id
 * @property int|null $payer_id
 * @property numeric $amount
 * @property string $status
 * @property string|null $description
 * @property string|null $redirect_url
 * @property string|null $webhook_url
 * @property array<array-key, mixed>|null $metadata
 * @property \Illuminate\Support\Carbon|null $paid_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\User $merchant
 * @property-read \App\Models\User|null $payer
 * @method static \Database\Factories\MerchantPaymentFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MerchantPayment newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MerchantPayment newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MerchantPayment query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MerchantPayment whereAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MerchantPayment whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MerchantPayment whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MerchantPayment whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MerchantPayment whereMerchantId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MerchantPayment whereMetadata($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MerchantPayment wherePaidAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MerchantPayment wherePayerId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MerchantPayment whereRedirectUrl($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MerchantPayment whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MerchantPayment whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MerchantPayment whereWebhookUrl($value)
 * @mixin \Eloquent
 */
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
