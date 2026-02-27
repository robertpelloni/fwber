<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ZkProximityProof extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'target_entity_type',
        'target_entity_id',
        'proof_hash',
        'is_verified',
    ];

    protected $casts = [
        'is_verified' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
