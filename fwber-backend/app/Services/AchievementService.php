<?php

namespace App\Services;

use App\Models\Achievement;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class AchievementService
{
    protected $tokenService;

    public function __construct(TokenDistributionService $tokenService)
    {
        $this->tokenService = $tokenService;
    }

    public function checkAndUnlock(User $user, string $criteriaType, int $currentValue): array
    {
        $unlocked = [];

        // Find achievements of this type that the user hasn't unlocked yet
        // and where the criteria value is met
        $achievements = Achievement::where('criteria_type', $criteriaType)
            ->where('criteria_value', '<=', $currentValue)
            ->whereDoesntHave('users', function ($query) use ($user) {
                $query->where('user_id', $user->id);
            })
            ->get();

        foreach ($achievements as $achievement) {
            DB::transaction(function () use ($user, $achievement, &$unlocked) {
                $user->achievements()->attach($achievement->id, ['unlocked_at' => now()]);
                
                if ($achievement->reward_tokens > 0) {
                    $this->tokenService->awardTokens(
                        $user, 
                        $achievement->reward_tokens, 
                        'achievement_unlocked', 
                        "Unlocked achievement: {$achievement->name}"
                    );
                }

                $unlocked[] = $achievement;
            });
        }

        return $unlocked;
    }

    public function getProgress(User $user)
    {
        $allAchievements = Achievement::all();
        $userAchievements = $user->achievements()->get()->keyBy('id');

        return $allAchievements->map(function ($achievement) use ($userAchievements) {
            $isUnlocked = $userAchievements->has($achievement->id);
            
            return [
                'id' => $achievement->id,
                'name' => $achievement->name,
                'description' => $achievement->description,
                'icon' => $achievement->icon,
                'category' => $achievement->category,
                'reward_tokens' => $achievement->reward_tokens,
                'is_unlocked' => $isUnlocked,
                'unlocked_at' => $isUnlocked ? $userAchievements[$achievement->id]->pivot->unlocked_at : null,
                'is_hidden' => $achievement->is_hidden && !$isUnlocked,
            ];
        });
    }
}
