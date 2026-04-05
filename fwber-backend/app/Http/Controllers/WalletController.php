<?php

namespace App\Http\Controllers;

use App\Http\Requests\Token\UpdateWalletAddressRequest;
use App\Models\WalletTransaction;
use App\Services\ReferralCommissionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WalletController extends Controller
{
    public function __construct(private readonly ReferralCommissionService $referralCommissionService)
    {
    }

    public function show(Request $request): JsonResponse
    {
        $user = $request->user()->fresh();

        $transactions = WalletTransaction::query()
            ->where('user_id', $user->id)
            ->latest()
            ->limit(25)
            ->get(['id', 'amount', 'type', 'description', 'created_at']);

        // The wallet page is now the practical home for the restored referral and
        // payout surface. We merge the dedicated referral summary into the wallet
        // payload so legacy token/payout links land on one coherent screen.
        $referralSummary = $this->referralCommissionService->buildSummary($user);

        return response()->json([
            'balance' => number_format((float) ($user->token_balance ?? 0), 2, '.', ''),
            'referral_code' => $referralSummary['referral_code'] ?? $user->referral_code,
            'wallet_address' => $user->wallet_address,
            'transactions' => $transactions,
            'referral_count' => (int) ($referralSummary['referral_count'] ?? 0),
            'golden_tickets_remaining' => (int) ($user->golden_tickets_remaining ?? 0),
            'treasury_address' => null,
            'mint_address' => null,
            'referral_link' => $referralSummary['referral_link'] ?? null,
            'vouch_link' => $referralSummary['vouch_link'] ?? null,
            'vouches_count' => (int) ($referralSummary['vouches_count'] ?? 0),
            'pending_cash_usd' => (float) ($referralSummary['pending_cash_usd'] ?? 0),
            'earned_token_rewards' => (float) ($referralSummary['earned_token_rewards'] ?? 0),
            'levels' => $referralSummary['levels'] ?? [],
            'recent_commissions' => $referralSummary['recent_commissions'] ?? [],
            'reward_rules' => $referralSummary['reward_rules'] ?? [],
        ]);
    }

    public function updateAddress(UpdateWalletAddressRequest $request): JsonResponse
    {
        $user = $request->user();
        $user->wallet_address = $request->validated()['wallet_address'];
        $user->save();

        return response()->json([
            'message' => 'Wallet address updated successfully.',
            'wallet_address' => $user->wallet_address,
        ]);
    }
}
