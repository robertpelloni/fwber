<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\ProximityArtifact;
use App\Models\User;

class ProximityArtifactVote extends Model
{
    use HasFactory;

    protected $fillable = [
        'proximity_artifact_id',
        'user_id',
        'vote',
    ];

    public function artifact()
    {
        return $this->belongsTo(ProximityArtifact::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
