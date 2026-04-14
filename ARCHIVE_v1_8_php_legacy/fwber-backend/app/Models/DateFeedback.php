<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $match_id
 * @property int $reporting_user_id
 * @property int $subject_user_id
 * @property int $rating 1-5 stars
 * @property string|null $feedback_text
 * @property bool $safety_concerns
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\UserMatch $match
 * @property-read \App\Models\User $reporter
 * @property-read \App\Models\User $subject
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DateFeedback newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DateFeedback newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DateFeedback query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DateFeedback whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DateFeedback whereFeedbackText($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DateFeedback whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DateFeedback whereMatchId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DateFeedback whereRating($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DateFeedback whereReportingUserId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DateFeedback whereSafetyConcerns($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DateFeedback whereSubjectUserId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DateFeedback whereUpdatedAt($value)
 *
 * @mixin \Eloquent
 */
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
