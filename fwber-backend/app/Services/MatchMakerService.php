<?php

namespace App\Services;

use App\Models\MatchAssist;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class MatchMakerService
{
    protected TokenDistributionService $tokenService;

    const MATCHMAKER_BONUS = 50; // Tokens awarded for a successful match

    public function __construct(TokenDistributionService $tokenService)
    {
        $this->tokenService = $tokenService;
    }

    /**
     * Record an assist when a user views a profile via a wingman link.
     */
    public function recordAssist(int $matchmakerId, int $subjectId, int $targetId): void
    {
        // Prevent self-referral
        if ($matchmakerId === $targetId || $matchmakerId === $subjectId) {
            return;
        }

        // Prevent duplicate assists
        MatchAssist::firstOrCreate(
            [
                'matchmaker_id' => $matchmakerId,
                'subject_id' => $subjectId,
                'target_id' => $targetId,
            ],
            [
                'status' => 'viewed',
            ]
        );
    }

    /**
     * Process a new match to see if any wingmen should be rewarded.
     */
    public function processMatch(int $user1Id, int $user2Id): void
    {
        // Check for assists in both directions
        // Case 1: Wingman shared User1 with User2
        $assists1 = MatchAssist::where('subject_id', $user1Id)
            ->where('target_id', $user2Id)
            ->where('status', '!=', 'matched')
            ->get();

        // Case 2: Wingman shared User2 with User1
        $assists2 = MatchAssist::where('subject_id', $user2Id)
            ->where('target_id', $user1Id)
            ->where('status', '!=', 'matched')
            ->get();

        $allAssists = $assists1->merge($assists2);

        foreach ($allAssists as $assist) {
            $this->rewardWingman($assist);
        }
    }

    protected function rewardWingman(MatchAssist $assist): void
    {
        DB::transaction(function () use ($assist) {
            $assist->update(['status' => 'matched']);

            $wingman = $assist->matchmaker;
            
            $this->tokenService->awardTokens(
                $wingman,
                self::MATCHMAKER_BONUS,
                'matchmaker_bonus',
                "Wingman Bonus: You successfully matched User {$assist->subject_id} and User {$assist->target_id}!"
            );
        });
    }
}
