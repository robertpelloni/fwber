<?php

namespace App\Services;

use App\Models\User;
use App\Models\TokenTransaction;
use App\Notifications\PushMessage;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class TokenDistributionService
{
    const SIGNUP_BONUS_BASE = 100;
    const REFERRAL_BONUS = 50;
    const MATCHMAKER_BONUS = 50;
    const EARLY_ADOPTER_MULTIPLIER_DECAY = 0.0001; // Decay factor per user

    public function generateReferralCode(): string
    {
        do {
            $code = Str::upper(Str::random(8));
        } while (User::where('referral_code', $code)->exists());

        return $code;
    }

    public function processSignupBonus(User $user, ?string $referrerCode = null): void
    {
        DB::transaction(function () use ($user, $referrerCode) {
            // 1. Calculate Early Adopter Bonus
            // Formula: Base * (1 / (1 + (UserCount * Decay)))
            // This creates a curve where early users get significantly more
            $userCount = User::count();
            $multiplier = 1 / (1 + ($userCount * self::EARLY_ADOPTER_MULTIPLIER_DECAY));
            $amount = self::SIGNUP_BONUS_BASE * $multiplier;
            
            // Round to 2 decimal places
            $amount = round($amount, 2);

            $this->awardTokens($user, $amount, 'signup_bonus', 'Early Adopter Signup Bonus');

            // 2. Process Referral
            if ($referrerCode) {
                $referrer = User::where('referral_code', $referrerCode)->first();
                if ($referrer) {
                    $user->referrer_id = $referrer->id;
                    $user->save();

                    // Check for Golden Ticket
                    if ($referrer->golden_tickets_remaining > 0) {
                        $referrer->decrement('golden_tickets_remaining');
                        
                        // Grant 3 Days of Premium (Gold Tier)
                        $user->tier = 'gold';
                        $user->tier_expires_at = now()->addDays(3);
                        $user->save();
                    }

                    // Award Referrer
                    $this->awardTokens($referrer, self::REFERRAL_BONUS, 'referral_bonus', "Referral Bonus for user {$user->id}");

                    // Check for Referral Achievements
                    try {
                        $achievementService = app(\App\Services\AchievementService::class);
                        $referralCount = $referrer->referrals()->count();
                        $achievementService->checkAndUnlock($referrer, 'referrals_count', $referralCount);
                    } catch (\Exception $e) {
                        Log::error("Achievement check failed: " . $e->getMessage());
                    }
                    
                    // Award Referee (Double-sided airdrop: both get 50)
                    $this->awardTokens($user, self::REFERRAL_BONUS, 'referral_accepted_bonus', "Bonus for using referral code");
                }
            }
        });
    }

    public function awardTokens(User $user, float $amount, string $type, string $description): void
    {
        $user->token_balance += $amount;
        $user->save();

        TokenTransaction::create([
            'user_id' => $user->id,
            'amount' => $amount,
            'type' => $type,
            'description' => $description,
        ]);

        try {
            $user->notify(new PushMessage(
                "💰 Tokens Received!",
                "You received {$amount} tokens: {$description}",
                "/wallet",
                "wallet"
            ));
        } catch (\Exception $e) {
            // Fail silently if notification service is down or configured incorrectly
            Log::error("Notification failed in awardTokens: " . $e->getMessage());
        }
    }

    public function spendTokens(User $user, float $amount, string $description): void
    {
        if ($user->token_balance < $amount) {
            throw new \Exception("Insufficient token balance.");
        }

        DB::transaction(function () use ($user, $amount, $description) {
            $user->token_balance -= $amount;
            $user->save();

            TokenTransaction::create([
                'user_id' => $user->id,
                'amount' => -$amount,
                'type' => 'spend',
                'description' => $description,
            ]);
        });
    }
}
