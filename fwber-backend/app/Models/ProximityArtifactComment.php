<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\ProximityArtifact;
use App\Models\User;

use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class ProximityArtifactComment extends Model
{
    use HasFactory, SoftDeletes, HasUuids;

    protected $fillable = [
        'proximity_artifact_id',
        'user_id',
        'content',
        'parent_id',
    ];

    public function artifact()
    {
        return $this->belongsTo(ProximityArtifact::class, 'proximity_artifact_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
    
    public function parent()
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function replies()
    {
        return $this->hasMany(self::class, 'parent_id');
    }
}
