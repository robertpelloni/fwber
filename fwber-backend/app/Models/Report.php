<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Report extends Model
{
    use HasFactory;

    protected $fillable = [
        'reporter_id','accused_id','message_id','reason','details','status','resolution_notes','moderator_id'
    ];

    public function reporter()
    {
        return $this->belongsTo(User::class, 'reporter_id');
    }

    public function accused()
    {
        return $this->belongsTo(User::class, 'accused_id');
    }

    public function moderator()
    {
        return $this->belongsTo(User::class, 'moderator_id');
    }
}
