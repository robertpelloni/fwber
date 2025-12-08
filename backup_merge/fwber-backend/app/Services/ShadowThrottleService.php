<?php

namespace App\Services;

use App\Models\ShadowThrottle;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ShadowThrottleService
{
    /**
     * Apply shadow throttle to a user.
     *
     * @param int $userId
     * @param string $reason
     * @param int $severity (1-5)
     * @param int|null $durationHours
     * @param int|null $createdBy Moderator user ID
     * @param string|null $notes
     * @return ShadowThrottle
     */
    public function applyThrottle(
        int $userId,
        string $reason,
        int $severity = 1,
        ?int $durationHours = null,
        ?int $createdBy = null,
        ?string $notes = null
    ): ShadowThrottle {
        // Calculate visibility reduction based on severity
        $visibilityReduction = $this->calculateVisibilityReduction($severity);
        
        $expiresAt = $durationHours 
            ? now()->addHours($durationHours)
            : null; // Null = permanent until manually removed

        return ShadowThrottle::create([
            'user_id' => $userId,
            'reason' => $reason,
            'severity' => $severity,
            'visibility_reduction' => $visibilityReduction,
            'started_at' => now(),
            'expires_at' => $expiresAt,
            'notes' => $notes,
            'created_by' => $createdBy,
        ]);
    }

    /**
     * Calculate visibility reduction based on severity.
     * Severity 1 = 0.70 (30% reduction)
     * Severity 2 = 0.50 (50% reduction)
     * Severity 3 = 0.30 (70% reduction)
     * Severity 4 = 0.15 (85% reduction)
     * Severity 5 = 0.05 (95% reduction)
     */
    private function calculateVisibilityReduction(int $severity): float
    {
        return match($severity) {
            1 => 0.70,
            2 => 0.50,
            3 => 0.30,
            4 => 0.15,
            5 => 0.05,
            default => 0.50,
        };
    }

    /**
     * Get active throttle for a user.
     */
    public function getActiveThrottle(int $userId): ?ShadowThrottle
    {
        return ShadowThrottle::where('user_id', $userId)
            ->active()
            ->orderBy('severity', 'desc')
            ->orderBy('started_at', 'desc')
            ->first();
    }

    /**
     * Get visibility multiplier for a user (0.0 - 1.0).
     * Returns 1.0 if no active throttle, or the reduction factor if throttled.
     */
    public function getVisibilityMultiplier(int $userId): float
    {
        $throttle = $this->getActiveThrottle($userId);
        
        return $throttle ? (float) $throttle->visibility_reduction : 1.0;
    }

    /**
     * Check if user is currently throttled.
     */
    public function isThrottled(int $userId): bool
    {
        return $this->getActiveThrottle($userId) !== null;
    }

    /**
     * Remove all active throttles for a user.
     */
    public function removeThrottle(int $userId): int
    {
        return ShadowThrottle::where('user_id', $userId)
            ->active()
            ->delete();
    }

    /**
     * Apply automatic throttle based on flag count.
     */
    public function applyAutoThrottleForFlags(int $userId, int $flagCount): ?ShadowThrottle
    {
        // Progressive throttling based on flag count
        if ($flagCount >= 10) {
            return $this->applyThrottle($userId, 'flagged_content', 4, 168, null, "Auto-throttle: {$flagCount} flags");
        } elseif ($flagCount >= 5) {
            return $this->applyThrottle($userId, 'flagged_content', 3, 72, null, "Auto-throttle: {$flagCount} flags");
        } elseif ($flagCount >= 3) {
            return $this->applyThrottle($userId, 'flagged_content', 2, 24, null, "Auto-throttle: {$flagCount} flags");
        }

        return null;
    }

    /**
     * Apply throttle for rapid posting (spam behavior).
     */
    public function applySpamThrottle(int $userId, int $postCount): ShadowThrottle
    {
        $severity = min(5, ceil($postCount / 10));
        $durationHours = $severity * 12; // 12-60 hours based on severity

        return $this->applyThrottle(
            $userId,
            'rapid_posting',
            $severity,
            $durationHours,
            null,
            "Auto-throttle: {$postCount} posts in short time"
        );
    }

    /**
     * Clean up expired throttles (can be run as scheduled task).
     */
    public function pruneExpired(): int
    {
        return ShadowThrottle::whereNotNull('expires_at')
            ->where('expires_at', '<', now())
            ->delete();
    }

    /**
     * Get throttle statistics for a user.
     */
    public function getUserThrottleStats(int $userId): array
    {
        $total = ShadowThrottle::where('user_id', $userId)->count();
        $active = ShadowThrottle::where('user_id', $userId)->active()->count();
        $currentMultiplier = $this->getVisibilityMultiplier($userId);

        return [
            'total_throttles' => $total,
            'active_throttles' => $active,
            'current_visibility' => $currentMultiplier,
            'is_throttled' => $active > 0,
        ];
    }
}
