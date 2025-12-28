<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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
