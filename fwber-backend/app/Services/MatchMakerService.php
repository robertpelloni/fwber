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
        // Generate a unique slug
        $slug = $this->generateUniqueSlug();

        return MatchBounty::create([
            'user_id' => $user->id,
            'slug' => $slug,
            'token_reward' => $tokenReward,
            'status' => 'active',
            // Optional: Set expiration, e.g., 30 days
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
     *
     * @param MatchBounty $bounty
     * @param User $matchmaker
     * @param User $candidate
     * @return MatchAssist
     */
    public function suggestCandidate(MatchBounty $bounty, User $matchmaker, User $candidate): MatchAssist
    {
        // Don't allow suggesting the bounty creator themselves
        if ($candidate->id === $bounty->user_id) {
             throw new \Exception("Cannot suggest the bounty creator to themselves.");
        }

        // Don't allow suggesting yourself if you are the matchmaker (optional logic, but makes sense)
        if ($candidate->id === $matchmaker->id) {
             // throw new \Exception("Cannot suggest yourself."); 
             // Actually, maybe you can? Let's leave it open for now or add restriction later if needed.
        }

        return MatchAssist::create([
            'match_bounty_id' => $bounty->id,
            'matchmaker_id' => $matchmaker->id,
            'subject_id' => $bounty->user_id, // The bounty creator is the subject being helped
            'target_id' => $candidate->id,     // The candidate is the target being suggested
            'status' => 'suggested',
        ]);
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
            
            Log::info("Wingman Reward: User {$wingman->id} matched {$assist->subject_id} and {$assist->target_id}");

            $this->tokenService->awardTokens(
                $wingman,
                self::MATCHMAKER_BONUS,
                'matchmaker_bonus',
                "Wingman Bonus: You successfully matched User {$assist->subject_id} and User {$assist->target_id}!"
            );
        });
    }
}
