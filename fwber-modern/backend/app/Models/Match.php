<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserMatch extends Model
{
    use HasFactory;

    protected $fillable = [
        'user1_id',
        'user2_id',
        'status',
        'match_score',
        'matched_at',
        'responded_at',
    ];

    protected $casts = [
        'matched_at' => 'datetime',
        'responded_at' => 'datetime',
        'match_score' => 'integer',
    ];

    public function user1()
    {
        return $this->belongsTo(FwberUser::class, 'user1_id');
    }

    public function user2()
    {
        return $this->belongsTo(FwberUser::class, 'user2_id');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeAccepted($query)
    {
        return $query->where('status', 'accepted');
    }

    public function scopeRejected($query)
    {
        return $query->where('status', 'rejected');
    }

    public function scopeBlocked($query)
    {
        return $query->where('status', 'blocked');
    }
}
