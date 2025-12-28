<?php

namespace App\Services;

use App\Models\User;
use Carbon\Carbon;

class StreakService
{
    public function updateStreak(User $user)
    {
        $now = Carbon::now();
        $lastActive = $user->last_active_at;

        if (!$lastActive) {
            $user->update([
                'current_streak' => 1,
                'last_active_at' => $now,
            ]);
            return;
        }

        if ($lastActive->isToday()) {
            return;
        }

        if ($lastActive->isYesterday()) {
            $user->increment('current_streak');
            $user->update(['last_active_at' => $now]);
            
            // Set flag on user object instance so frontend knows to celebrate
            $user->streakJustUpdated = true;
        } else {
            // Missed a day (or more)
            $user->update([
                'current_streak' => 1,
                'last_active_at' => $now,
            ]);
        }
    }
}
