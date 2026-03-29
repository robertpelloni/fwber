<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $to_user_id
 * @property int|null $from_user_id
 * @property string|null $voucher_name
 * @property string $type
 * @property string|null $relationship_type
 * @property string|null $comment
 * @property int $is_verified
 * @property string|null $ip_address
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\User $recipient
 * @property-read \App\Models\User|null $sender
 * @method static \Database\Factories\VouchFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Vouch newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Vouch newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Vouch query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Vouch whereComment($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Vouch whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Vouch whereFromUserId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Vouch whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Vouch whereIpAddress($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Vouch whereIsVerified($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Vouch whereRelationshipType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Vouch whereToUserId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Vouch whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Vouch whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Vouch whereVoucherName($value)
 * @mixin \Eloquent
 */
class Vouch extends Model
{
    use HasFactory;

    protected $fillable = [
        'to_user_id',
        'from_user_id',
        'type',
        'ip_address',
        'relationship_type',
        'comment',
        'voucher_name',
    ];

    public function recipient()
    {
        return $this->belongsTo(User::class, 'to_user_id');
    }

    public function sender()
    {
        return $this->belongsTo(User::class, 'from_user_id');
    }
}
