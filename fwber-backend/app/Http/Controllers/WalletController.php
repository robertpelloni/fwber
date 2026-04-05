<?php

namespace App\Http\Controllers;

use App\Http\Requests\Token\UpdateWalletAddressRequest;
use App\Models\WalletTransaction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WalletController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        $user = $request->user()->fresh();

        $transactions = WalletTransaction::query()
            ->where('user_id', $user->id)
            ->latest()
            ->limit(25)
            ->get(['id', 'amount', 'type', 'description', 'created_at']);

        return response()->json([
            'balance' => number_format((float) ($user->token_balance ?? 0), 2, '.', ''),
            'referral_code' => $user->referral_code,
            'wallet_address' => $user->wallet_address,
            'transactions' => $transactions,
            'referral_count' => 0,
            'golden_tickets_remaining' => (int) ($user->golden_tickets_remaining ?? 0),
            'treasury_address' => null,
            'mint_address' => null,
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
