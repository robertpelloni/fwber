<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\ProximityArtifact;
use App\Models\User;

use Illuminate\Database\Eloquent\Concerns\HasUuids;

class ProximityArtifactVote extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'proximity_artifact_id',
        'user_id',
        'value',
    ];

    public function artifact()
    {
        return $this->belongsTo(ProximityArtifact::class, 'proximity_artifact_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
