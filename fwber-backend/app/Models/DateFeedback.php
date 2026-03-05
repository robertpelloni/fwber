<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DateFeedback extends Model
{
    use HasFactory;

    protected $fillable = [
        'match_id',
        'reporting_user_id',
        'subject_user_id',
        'rating',
        'feedback_text',
        'safety_concerns',
    ];

    protected $casts = [
        'rating' => 'integer',
        'safety_concerns' => 'boolean',
    ];

    public function match()
    {
        return $this->belongsTo(UserMatch::class, 'match_id');
    }

    public function reporter()
    {
        return $this->belongsTo(User::class, 'reporting_user_id');
    }

    public function subject()
    {
        return $this->belongsTo(User::class, 'subject_user_id');
    }
}
