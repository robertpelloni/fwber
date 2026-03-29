<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $user_id
 * @property string $reason
 * @property int $severity
 * @property numeric $visibility_reduction
 * @property \Illuminate\Support\Carbon $started_at
 * @property \Illuminate\Support\Carbon|null $expires_at
 * @property string|null $notes
 * @property int|null $created_by
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\User|null $creator
 * @property-read \App\Models\User $user
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShadowThrottle active()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShadowThrottle newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShadowThrottle newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShadowThrottle query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShadowThrottle whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShadowThrottle whereCreatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShadowThrottle whereExpiresAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShadowThrottle whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShadowThrottle whereNotes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShadowThrottle whereReason($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShadowThrottle whereSeverity($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShadowThrottle whereStartedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShadowThrottle whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShadowThrottle whereUserId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShadowThrottle whereVisibilityReduction($value)
 * @mixin \Eloquent
 */
class ShadowThrottle extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'reason',
        'severity',
        'visibility_reduction',
        'started_at',
        'expires_at',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'severity' => 'integer',
        'visibility_reduction' => 'decimal:2',
        'started_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    /**
     * Normalize reason to fit enum constraints while preserving free-text in notes.
     * If an unknown reason is provided, map it to 'manual' and store the original
     * reason in notes (appending if notes already set).
     */
    public function setReasonAttribute(string $value): void
    {
        $allowed = ['spam', 'flagged_content', 'geo_spoof', 'rapid_posting', 'manual'];
        if (! in_array($value, $allowed, true)) {
            // Preserve original in notes
            $original = trim($value);
            $notes = (string) ($this->attributes['notes'] ?? '');
            $this->attributes['notes'] = trim($notes !== '' ? ($notes.' | reason: '.$original) : ('reason: '.$original));
            $this->attributes['reason'] = 'manual';

            return;
        }

        $this->attributes['reason'] = $value;
    }

    /**
     * Get the user being throttled.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the moderator who created this throttle.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Check if this throttle is currently active.
     */
    public function isActive(): bool
    {
        $now = now();

        if ($now->lessThan($this->started_at)) {
            return false;
        }

        if ($this->expires_at && $now->greaterThan($this->expires_at)) {
            return false;
        }

        return true;
    }

    /**
     * Scope to get only active throttles.
     */
    public function scopeActive($query)
    {
        return $query->where('started_at', '<=', now())
            ->where(function ($q) {
                $q->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            });
    }
}
