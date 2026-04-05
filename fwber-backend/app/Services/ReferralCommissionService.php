<?php

namespace App\Services;

use App\Models\Payment;
use App\Models\ReferralCommission;
use App\Models\Subscription;
use App\Models\User;
use App\Models\WalletTransaction;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * Restored referral engine kept intentionally compact for production safety.
 *
 * The earlier platform had a much larger token-distribution subsystem. During
 * simplification that machinery was removed, but the user-facing referral,
 * payout, vouch, and premium-upgrade expectations remained in the product and
 * frontend. This service restores the practical core:
 *
 * - stable referral code issuance
 * - signup referral rewards
 * - two-level premium commissions
 * - pending cash payout visibility
 * - wallet transaction side effects for earned token rewards
 */
class ReferralCommissionService
{
    public function ensureReferralCode(User $user): User
    {
        if (! Schema::hasColumn('users', 'referral_code') || ! empty($user->referral_code)) {
            return $user;
        }

        $user->forceFill([
            'referral_code' => $this->generateUniqueCode($user->name ?: 'FWB'),
        ])->save();

        return $user->refresh();
    }

    public function lookupReferrer(?string $referralCode): ?User
    {
        if (! $referralCode || ! Schema::hasColumn('users', 'referral_code')) {
            return null;
        }

        return User::query()->where('referral_code', $referralCode)->first();
    }

    public function awardSignupRewards(User $referredUser, User $referrer): void
    {
        if (! Schema::hasTable('wallet_transactions')) {
            return;
        }

        $rewardForReferrer = (float) config('referrals.signup.referrer_token_amount', 50);
        $rewardForNewUser = (float) config('referrals.signup.referred_token_amount', 50);

        if ($rewardForReferrer > 0) {
            $this->creditTokens(
                $referrer,
                $rewardForReferrer,
                'referral_signup_bonus',
                sprintf('Referral signup bonus from %s joining fwber', $referredUser->name)
            );
        }

        if ($rewardForNewUser > 0) {
            $this->creditTokens(
                $referredUser,
                $rewardForNewUser,
                'referred_signup_bonus',
                sprintf('Welcome referral bonus for joining from %s', $referrer->name)
            );
        }
    }

    public function awardPremiumCommissions(
        User $purchaser,
        ?Subscription $subscription = null,
        ?Payment $payment = null,
        string $paymentMethod = 'stripe'
    ): void {
        if (! Schema::hasTable('referral_commissions') || ! Schema::hasColumn('users', 'referrer_id')) {
            return;
        }

        $purchaser->loadMissing('referrer.referrer');
        $ancestor = $purchaser->referrer;

        for ($level = 1; $level <= 2 && $ancestor; $level++) {
            $cashAmount = $this->cashRewardForLevel($level);
            $tokenAmount = $this->tokenRewardForLevel($level);

            if ($cashAmount <= 0 && $tokenAmount <= 0) {
                $ancestor = $ancestor->referrer;
                continue;
            }

            $commissionKey = sprintf(
                'premium:%s:%s:beneficiary:%d:level:%d',
                $payment?->id ? 'payment' : 'subscription',
                $payment?->id ?: ($subscription?->id ?: $purchaser->id),
                $ancestor->id,
                $level,
            );

            $commission = ReferralCommission::firstOrCreate(
                ['commission_key' => $commissionKey],
                [
                    'purchaser_user_id' => $purchaser->id,
                    'beneficiary_user_id' => $ancestor->id,
                    'payment_id' => $payment?->id,
                    'subscription_id' => $subscription?->id,
                    'level' => $level,
                    'cash_amount' => $cashAmount,
                    'cash_currency' => 'USD',
                    'cash_status' => 'pending',
                    'token_amount' => $tokenAmount,
                    'source' => 'premium_purchase',
                    'metadata' => [
                        'payment_method' => $paymentMethod,
                        'plan' => $subscription?->name,
                        'purchaser_user_id' => $purchaser->id,
                    ],
                ]
            );

            if ($commission->wasRecentlyCreated && $tokenAmount > 0) {
                $this->creditTokens(
                    $ancestor,
                    $tokenAmount,
                    sprintf('premium_referral_level_%d', $level),
                    sprintf('Level %d premium referral reward from %s', $level, $purchaser->name)
                );
            }

            $ancestor = $ancestor->referrer;
        }
    }

    public function buildSummary(User $user): array
    {
        $user = $this->ensureReferralCode($user);

        $countRelations = [];
        if (Schema::hasColumn('users', 'referrer_id')) {
            $countRelations[] = 'referrals';
        }
        if (Schema::hasTable('vouches')) {
            $countRelations[] = 'vouches';
        }
        if (! empty($countRelations)) {
            $user->loadCount($countRelations);
        }

        /** @var Collection<int, ReferralCommission> $commissions */
        $commissions = Schema::hasTable('referral_commissions')
            ? ReferralCommission::query()
                ->where('beneficiary_user_id', $user->id)
                ->latest()
                ->get()
            : collect();

        $pendingCash = $commissions->where('cash_status', 'pending');
        $frontendUrl = rtrim((string) config('referrals.frontend_url', config('app.frontend_url', 'https://fwber.me')), '/');

        return [
            'referral_code' => $user->referral_code,
            'referral_link' => $frontendUrl.'/register?ref='.$user->referral_code,
            'vouch_link' => $frontendUrl.'/vouch/'.$user->referral_code,
            'referral_count' => (int) ($user->referrals_count ?? 0),
            'referrals_count' => (int) ($user->referrals_count ?? 0),
            'vouches_count' => (int) ($user->vouches_count ?? 0),
            'golden_tickets_remaining' => (int) $user->golden_tickets_remaining,
            'token_balance' => (float) ($user->token_balance ?? 0),
            'pending_cash_usd' => round((float) $pendingCash->sum('cash_amount'), 2),
            'earned_token_rewards' => round((float) $commissions->sum('token_amount'), 2),
            'levels' => collect([1, 2])->map(function (int $level) use ($commissions): array {
                $items = $commissions->where('level', $level);

                return [
                    'level' => $level,
                    'count' => $items->count(),
                    'cash_usd' => round((float) $items->sum('cash_amount'), 2),
                    'token_amount' => round((float) $items->sum('token_amount'), 2),
                ];
            })->values()->all(),
            'recent_commissions' => $commissions->take(10)->map(fn (ReferralCommission $commission): array => [
                'id' => $commission->id,
                'level' => (int) $commission->level,
                'cash_amount' => number_format((float) $commission->cash_amount, 2, '.', ''),
                'cash_status' => $commission->cash_status,
                'token_amount' => number_format((float) $commission->token_amount, 2, '.', ''),
                'source' => $commission->source,
                'created_at' => $commission->created_at?->toIso8601String(),
            ])->values()->all(),
            'reward_rules' => [
                'signup' => [
                    'referrer_token_amount' => (float) config('referrals.signup.referrer_token_amount', 50),
                    'referred_token_amount' => (float) config('referrals.signup.referred_token_amount', 50),
                ],
                'level_1' => [
                    'cash_usd' => $this->cashRewardForLevel(1),
                    'token_amount' => $this->tokenRewardForLevel(1),
                ],
                'level_2' => [
                    'cash_usd' => $this->cashRewardForLevel(2),
                    'token_amount' => $this->tokenRewardForLevel(2),
                ],
            ],
        ];
    }

    private function creditTokens(User $user, float $amount, string $type, string $description): void
    {
        if ($amount <= 0 || ! Schema::hasTable('wallet_transactions')) {
            return;
        }

        $user->forceFill([
            'token_balance' => round((float) $user->token_balance + $amount, 2),
        ])->save();

        WalletTransaction::query()->create([
            'user_id' => $user->id,
            'amount' => round($amount, 2),
            'type' => $type,
            'description' => $description,
        ]);
    }

    private function generateUniqueCode(string $seed): string
    {
        $prefix = strtoupper(substr(Str::slug($seed, ''), 0, 6) ?: 'FWB');

        do {
            $candidate = $prefix.random_int(1000, 9999);
        } while (User::query()->where('referral_code', $candidate)->exists());

        return $candidate;
    }

    private function cashRewardForLevel(int $level): float
    {
        return (float) config("referrals.premium.level_{$level}.cash_usd", 0);
    }

    private function tokenRewardForLevel(int $level): float
    {
        return (float) config("referrals.premium.level_{$level}.token_amount", 0);
    }
}
