<?php

namespace App\Services;

use App\Models\Payment;
use App\Models\ReferralCommission;
use App\Models\Subscription;
use App\Models\User;
use App\Notifications\PushMessage;
use Illuminate\Support\Collection;

class ReferralCommissionService
{
    public function __construct(private readonly TokenDistributionService $tokenDistributionService)
    {
    }

    public function awardPremiumCommissions(
        User $purchaser,
        Subscription $subscription,
        ?Payment $payment = null,
        string $paymentMethod = 'stripe'
    ): void {
        $purchaser->loadMissing('referrer.referrer');

        $ancestor = $purchaser->referrer;

        for ($level = 1; $level <= 2 && $ancestor; $level++) {
            $cashAmount = $payment ? $this->cashRewardForLevel($level) : 0.0;
            $tokenAmount = $this->tokenRewardForLevel($level);

            if ($cashAmount <= 0 && $tokenAmount <= 0) {
                $ancestor = $ancestor->referrer;
                continue;
            }

            $commissionKey = sprintf(
                'premium:%s:beneficiary:%d:level:%d',
                $payment ? 'payment-'.$payment->id : 'subscription-'.$subscription->id,
                $ancestor->id,
                $level
            );

            $commission = ReferralCommission::firstOrCreate(
                ['commission_key' => $commissionKey],
                [
                    'purchaser_user_id' => $purchaser->id,
                    'beneficiary_user_id' => $ancestor->id,
                    'payment_id' => $payment?->id,
                    'subscription_id' => $subscription->id,
                    'level' => $level,
                    'cash_amount' => $cashAmount,
                    'cash_currency' => 'USD',
                    'cash_status' => 'pending',
                    'token_amount' => $tokenAmount,
                    'source' => 'premium_purchase',
                    'metadata' => [
                        'payment_method' => $paymentMethod,
                        'purchaser_user_id' => $purchaser->id,
                    ],
                ]
            );

            if ($commission->wasRecentlyCreated && $tokenAmount > 0) {
                $this->tokenDistributionService->awardTokens(
                    $ancestor,
                    $tokenAmount,
                    'premium_referral_level_'.$level,
                    "Level {$level} premium referral reward from {$purchaser->name}",
                    [
                        'commission_id' => $commission->id,
                        'level' => $level,
                        'purchaser_user_id' => $purchaser->id,
                    ]
                );
            }

            if ($commission->wasRecentlyCreated && $cashAmount > 0) {
                try {
                    $ancestor->notify(new PushMessage(
                        'Referral payout pending',
                        "You earned \${$cashAmount} pending payout from {$purchaser->name}'s premium upgrade.",
                        '/dashboard',
                        'wallet'
                    ));
                } catch (\Throwable $exception) {
                    // Token rewards are the user-facing hard signal; a missed notification should not break the purchase flow.
                }
            }

            $ancestor = $ancestor->referrer;
        }
    }

    public function buildSummary(User $user): array
    {
        $this->tokenDistributionService->ensureReferralCode($user);
        $user->loadCount(['referrals', 'vouches']);

        /** @var Collection<int, ReferralCommission> $commissions */
        $commissions = ReferralCommission::query()
            ->where('beneficiary_user_id', $user->id)
            ->get();

        $pendingCash = $commissions->where('cash_status', 'pending');

        $levelSummaries = collect([1, 2])->map(function (int $level) use ($commissions) {
            $levelItems = $commissions->where('level', $level);

            return [
                'level' => $level,
                'count' => $levelItems->count(),
                'cash_usd' => (float) $levelItems->sum('cash_amount'),
                'token_amount' => (float) $levelItems->sum('token_amount'),
            ];
        })->values()->all();

        $frontendUrl = rtrim((string) config('referrals.frontend_url', 'https://fwber.me'), '/');

        return [
            'referral_code' => $user->referral_code,
            'referral_link' => $frontendUrl.'/register?ref='.$user->referral_code,
            'vouch_link' => $frontendUrl.'/vouch/'.$user->referral_code,
            'golden_tickets_remaining' => (int) $user->golden_tickets_remaining,
            'referrals_count' => (int) $user->referrals_count,
            'vouches_count' => (int) $user->vouches_count,
            'token_balance' => (float) $user->token_balance,
            'pending_cash_usd' => (float) $pendingCash->sum('cash_amount'),
            'earned_token_rewards' => (float) $commissions->sum('token_amount'),
            'levels' => $levelSummaries,
            'reward_rules' => [
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

    private function cashRewardForLevel(int $level): float
    {
        return (float) config("referrals.premium.level_{$level}.cash_usd", 0);
    }

    private function tokenRewardForLevel(int $level): float
    {
        return (float) config("referrals.premium.level_{$level}.token_amount", 0);
    }
}
