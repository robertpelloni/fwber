<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $user_id
 * @property string $business_name
 * @property string|null $description
 * @property string $category
 * @property string|null $address
 * @property string $verification_status
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Promotion> $promotions
 * @property-read int|null $promotions_count
 * @property-read \App\Models\User $user
 *
 * @method static \Database\Factories\MerchantProfileFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MerchantProfile newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MerchantProfile newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MerchantProfile query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MerchantProfile whereAddress($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MerchantProfile whereBusinessName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MerchantProfile whereCategory($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MerchantProfile whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MerchantProfile whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MerchantProfile whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MerchantProfile whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MerchantProfile whereUserId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MerchantProfile whereVerificationStatus($value)
 *
 * @mixin \Eloquent
 */
class MerchantProfile extends Model
{
    use HasFactory;

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

    public function promotions()
    {
        return $this->hasMany(Promotion::class, 'merchant_id');
    }
}
