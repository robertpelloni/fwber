<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int|null $user_id
 * @property string $category
 * @property string $message
 * @property string|null $page_url
 * @property array<array-key, mixed>|null $metadata
 * @property string $status
 * @property string|null $sentiment
 * @property string|null $ai_analysis
 * @property int $is_analyzed
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\User|null $user
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Feedback newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Feedback newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Feedback query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Feedback whereAiAnalysis($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Feedback whereCategory($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Feedback whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Feedback whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Feedback whereIsAnalyzed($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Feedback whereMessage($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Feedback whereMetadata($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Feedback wherePageUrl($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Feedback whereSentiment($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Feedback whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Feedback whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Feedback whereUserId($value)
 * @mixin \Eloquent
 */
class Feedback extends Model
{
    protected $fillable = [
        'user_id',
        'category',
        'message',
        'page_url',
        'metadata',
        'status',
        'sentiment',
        'ai_analysis',
        'is_analyzed',
    ];

    protected $casts = [
        'metadata' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
