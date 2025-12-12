<?php

namespace App\Services;

use App\Models\User;
use App\Models\TokenTransaction;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class TokenDistributionService
{
    const SIGNUP_BONUS_BASE = 100;
    const REFERRAL_BONUS = 50;
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

                    // Award Referrer
                    $this->awardTokens($referrer, self::REFERRAL_BONUS, 'referral_bonus', "Referral Bonus for user {$user->id}");
                    
                    // Award Referee (extra bonus for being referred?)
                    // Let's give them a small kicker, say 10% of base
                    $this->awardTokens($user, 10, 'referral_accepted_bonus', "Bonus for using referral code");
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
