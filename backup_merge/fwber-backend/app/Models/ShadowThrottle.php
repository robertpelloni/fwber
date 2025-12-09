<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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
        if (!in_array($value, $allowed, true)) {
            // Preserve original in notes
            $original = trim($value);
            $notes = (string) ($this->attributes['notes'] ?? '');
            $this->attributes['notes'] = trim($notes !== '' ? ($notes . ' | reason: ' . $original) : ('reason: ' . $original));
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
