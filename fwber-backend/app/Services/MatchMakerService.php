<?php

namespace App\Services;

use App\Models\MatchAssist;
use App\Models\MatchBounty;
use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class MatchMakerService
{
    protected TokenDistributionService $tokenService;

    const MATCHMAKER_BONUS = 50; // Tokens awarded for a successful match

    public function __construct(TokenDistributionService $tokenService)
    {
        $this->tokenService = $tokenService;
    }

    /**
     * Create a new match bounty for a user.
     */
    public function createBounty(User $user, int $tokenReward): MatchBounty
    {
        // 1. Escrow the tokens: Deduct from user immediately
        $this->tokenService->awardTokens(
            $user,
            -$tokenReward,
            'bounty_creation',
            "Escrow for match bounty: {$tokenReward} FWB"
        );

        // 2. Generate a unique slug
        $slug = $this->generateUniqueSlug();

        return MatchBounty::create([
            'user_id' => $user->id,
            'slug' => $slug,
            'token_reward' => $tokenReward,
            'status' => 'active',
            'expires_at' => now()->addDays(30),
        ]);
    }

    /**
     * Retrieve a bounty by its slug.
     */
    public function getBountyBySlug(string $slug): ?MatchBounty
    {
        return MatchBounty::where('slug', $slug)
            ->where('status', 'active')
            ->first();
    }

    /**
     * Generate a random, unique slug for the bounty.
     */
    protected function generateUniqueSlug(): string
    {
        do {
            $slug = Str::random(8); // Generates a random alphanumeric string
        } while (MatchBounty::where('slug', $slug)->exists());

        return $slug;
    }

    /**
     * Record a suggestion made by a matchmaker for a specific bounty.
     */
    public function suggestCandidate(MatchBounty $bounty, User $matchmaker, User $candidate): MatchAssist
    {
        if ($candidate->id === $bounty->user_id) {
             throw new \Exception("Cannot suggest the bounty creator to themselves.");
        }

        // Check if this pair was already suggested for this bounty
        $existing = MatchAssist::where('match_bounty_id', $bounty->id)
            ->where('matchmaker_id', $matchmaker->id)
            ->where('target_id', $candidate->id)
            ->first();

        if ($existing) {
            return $existing;
        }

        return MatchAssist::create([
            'match_bounty_id' => $bounty->id,
            'matchmaker_id' => $matchmaker->id,
            'subject_id' => $bounty->user_id,
            'target_id' => $candidate->id,
            'status' => 'suggested',
        ]);
    }

    /**
     * Record an assist when a user views a profile via a wingman link.
     */
    public function recordAssist(int $matchmakerId, int $subjectId, int $targetId): void
    {
        if ($matchmakerId === $targetId || $matchmakerId === $subjectId) {
            return;
        }

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

    public function processMatch(int $user1Id, int $user2Id): void
    {
        Log::info("ActivityPub: Processing match between {$user1Id} and {$user2Id}");

        // Check for assists in both directions
        $allAssists = MatchAssist::where(function ($q) use ($user1Id, $user2Id) {
                $q->where('subject_id', $user1Id)->where('target_id', $user2Id);
            })
            ->orWhere(function ($q) use ($user1Id, $user2Id) {
                $q->where('subject_id', $user2Id)->where('target_id', $user1Id);
            })
            ->where('status', '!=', 'matched')
            ->with('bounty')
            ->get();

        Log::info("ActivityPub: Found " . $allAssists->count() . " assists for match");

        foreach ($allAssists as $assist) {
            $this->rewardWingman($assist);
        }
    }

    protected function rewardWingman(MatchAssist $assist): void
    {
        DB::transaction(function () use ($assist) {
            $assist->update(['status' => 'matched']);

            $wingman = $assist->matchmaker;
            
            // Determine reward amount: Actual bounty reward or default bonus
            $rewardAmount = self::MATCHMAKER_BONUS;
            $memo = "Wingman Bonus: You successfully matched User {$assist->subject_id} and User {$assist->target_id}!";

            if ($assist->bounty) {
                $rewardAmount = $assist->bounty->token_reward;
                $memo = "Bounty Claimed! You matched User {$assist->subject_id} for their '{$assist->bounty->slug}' bounty.";

                // Mark bounty as fulfilled
                $assist->bounty->update(['status' => 'fulfilled']);
            }
            
            Log::info("Matchmaker Reward: User {$wingman->id} claimed {$rewardAmount} FWB for assist {$assist->id}");

            $this->tokenService->awardTokens(
                $wingman,
                $rewardAmount,
                'matchmaker_bonus',
                $memo
            );
        });
    }
}
