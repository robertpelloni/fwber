<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $requester_id
 * @property int $payer_id
 * @property numeric $amount
 * @property string|null $note
 * @property string $status
 * @property \Illuminate\Support\Carbon|null $paid_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\User $payer
 * @property-read \App\Models\User $requester
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PaymentRequest newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PaymentRequest newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PaymentRequest query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PaymentRequest whereAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PaymentRequest whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PaymentRequest whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PaymentRequest whereNote($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PaymentRequest wherePaidAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PaymentRequest wherePayerId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PaymentRequest whereRequesterId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PaymentRequest whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PaymentRequest whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class PaymentRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'requester_id',
        'payer_id',
        'amount',
        'note',
        'status',
        'paid_at',
    ];

    protected $casts = [
        'amount' => 'decimal:4',
        'paid_at' => 'datetime',
    ];

    public function requester()
    {
        return $this->belongsTo(User::class, 'requester_id');
    }

    public function payer()
    {
        return $this->belongsTo(User::class, 'payer_id');
    }
}
