<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SwapTransaction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SwapController extends Controller
{
    /**
     * Get swap history for the authenticated user.
     */
    public function index(): JsonResponse
    {
        $swaps = SwapTransaction::where('user_id', Auth::id())
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json(['swaps' => $swaps]);
    }

    /**
     * Initiate a token swap/bridge.
     */
    public function initiate(Request $request): JsonResponse
    {
        $user = Auth::user();
        $validated = $request->validate([
            'amount' => 'required|numeric|min:1',
            'target_asset' => 'required|string|in:SOL,USDC,PEER_TOKEN',
            'destination_address' => 'required|string|max:255',
        ]);

        if ($user->token_balance < $validated['amount']) {
            return response()->json(['error' => 'Insufficient FWB Token balance'], 400);
        }

        $swap = DB::transaction(function () use ($user, $validated) {
            // 1. Deduct tokens from local balance
            $user->decrement('token_balance', $validated['amount']);

            // 2. Create the swap record
            return SwapTransaction::create([
                'user_id' => $user->id,
                'source_asset' => 'FWB_TOKEN',
                'target_asset' => $validated['target_asset'],
                'source_amount' => $validated['amount'],
                'target_amount' => $validated['amount'] * 0.98, // 2% bridge fee
                'destination_address' => $validated['destination_address'],
                'status' => 'pending',
                'tx_hash' => 'BRIDGING_' . strtoupper(Str::random(12)),
            ]);
        });

        // 3. Trigger mock background bridge fulfillment
        // In a real system, this would be a queued job interacting with a ZK-bridge or relay
        \Illuminate\Support\Facades\Log::info("Token Swap Initiated: User {$user->id} bridging {$validated['amount']} to {$validated['target_asset']}");

        return response()->json([
            'message' => 'Swap initiated successfully. Funds are being bridged.',
            'swap' => $swap
        ]);
    }
}
