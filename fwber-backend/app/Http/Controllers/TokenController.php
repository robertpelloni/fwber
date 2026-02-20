<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\TokenTransaction;
use Illuminate\Support\Facades\Process;
use App\Http\Requests\Token\WithdrawTokenRequest;
use App\Http\Requests\Token\TransferTokenRequest;
use App\Http\Requests\Token\GetBalanceRequest;
use App\Http\Requests\Token\UpdateWalletAddressRequest;

class TokenController extends Controller
{
    public function withdraw(WithdrawTokenRequest $request)
    {
        $user = $request->user();
        $amount = $request->amount;

        // Atomic Check & Update
        $updated = User::where('id', $user->id)
            ->where('token_balance', '>=', $amount)
            ->decrement('token_balance', $amount);

        if (!$updated) {
            return response()->json(['error' => 'Insufficient balance'], 400);
        }

        $user->refresh();

        // Run Node Script safely using array arguments to prevent injection
        $scriptPath = base_path('scripts/solana/transfer_token.cjs');

        $process = Process::path(base_path())
            ->env([
                'SOLANA_SERVER_SECRET_KEY' => config('services.solana.server_secret_key'),
                'SOLANA_MINT_ADDRESS' => config('services.solana.mint_address'),
                'SOLANA_RPC_URL' => config('services.solana.rpc_url'),
            ])
            ->run(['node', $scriptPath, $request->destination_address, (string)$amount]);

        if ($process->failed()) {
            // Rollback
            $user->increment('token_balance', $amount);
            \Log::error('Solana Withdrawal Failed: ' . $process->errorOutput());
            return response()->json(['error' => 'Transfer failed. Please try again.'], 500);
        }

        $output = $process->output();
        preg_match('/TX_SIGNATURE: (\S+)/', $output, $matches);
        $signature = $matches[1] ?? 'pending';

        // Log Transaction
        $user->tokenTransactions()->create([
            'amount' => -$amount,
            'type' => 'withdrawal',
            'description' => 'Withdrawal to Solana',
            'metadata' => [
                'signature' => $signature,
                'destination' => $request->destination_address,
                'chain' => 'solana'
            ],
        ]);

        return response()->json([
            'message' => 'Withdrawal successful',
            'signature' => $signature,
            'new_balance' => $user->token_balance
        ]);
    }

    public function transfer(TransferTokenRequest $request)
    {
        $sender = $request->user();
        $recipient = User::findOrFail($request->recipient_id);
        $amount = $request->amount;

        if ($sender->id === $recipient->id) {
             return response()->json(['error' => 'Cannot tip yourself'], 400);
        }

        // Check balance atomically inside transaction? Or before?
        // Better to check before but enforce atomic decrement inside.
        if ($sender->token_balance < $amount) {
            return response()->json(['error' => 'Insufficient balance'], 400);
        }

        try {
            \DB::transaction(function () use ($sender, $recipient, $amount) {
                $deducted = User::where('id', $sender->id)
                    ->where('token_balance', '>=', $amount)
                    ->decrement('token_balance', $amount);

                if (!$deducted) {
                    throw new \Exception('Insufficient balance');
                }

                $recipient->increment('token_balance', $amount);

                $sender->tokenTransactions()->create([
                'amount' => -$amount,
                'type' => 'tip_sent',
                'description' => "Tip to {$recipient->name}",
                'metadata' => ['recipient_id' => $recipient->id],
            ]);

            $recipient->tokenTransactions()->create([
                'amount' => $amount,
                'type' => 'tip_received',
                'description' => "Tip from {$sender->name}",
                'metadata' => ['sender_id' => $sender->id],
                ]);
            });
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }

        return response()->json(['message' => 'Tip sent successfully', 'new_balance' => $sender->fresh()->token_balance]);
    }

    public function balance(GetBalanceRequest $request)
    {
        $user = $request->user();
        
        return response()->json([
            'balance' => $user->token_balance,
            'referral_code' => $user->referral_code,
            'wallet_address' => $user->wallet_address,
            'transactions' => $user->tokenTransactions()->latest()->take(20)->get(),
            'referral_count' => $user->referrals()->count(),
            'golden_tickets_remaining' => $user->golden_tickets_remaining,
        ]);
    }

    public function updateAddress(UpdateWalletAddressRequest $request)
    {
        $user = $request->user();
        $user->wallet_address = $request->wallet_address;
        $user->save();

        return response()->json(['message' => 'Wallet address updated']);
    }

    public function leaderboard()
    {
        // Cache this query in production!
        $topHolders = User::orderByDesc('token_balance')
            ->take(10)
            ->get(['id', 'name', 'token_balance', 'created_at'])
            ->map(function ($user) {
                return [
                    'name' => substr($user->name, 0, 3) . '***', // Privacy
                    'balance' => $user->token_balance,
                    'joined' => $user->created_at->diffForHumans(),
                ];
            });

        $topReferrers = User::withCount('referrals')
            ->orderByDesc('referrals_count')
            ->take(10)
            ->get(['id', 'name', 'referrals_count'])
            ->map(function ($user) {
                return [
                    'name' => substr($user->name, 0, 3) . '***',
                    'referrals' => $user->referrals_count,
                ];
            });

        $topWingmen = \App\Models\MatchAssist::select('matchmaker_id', \DB::raw('count(*) as assists_count'))
            ->where('status', 'matched')
            ->groupBy('matchmaker_id')
            ->orderByDesc('assists_count')
            ->take(10)
            ->with('matchmaker:id,name')
            ->get()
            ->map(function ($assist) {
                return [
                    'name' => substr($assist->matchmaker->name, 0, 3) . '***',
                    'assists' => $assist->assists_count,
                ];
            });

        $topVouched = User::withCount('vouches')
            ->orderByDesc('vouches_count')
            ->take(10)
            ->get(['id', 'name'])
            ->map(function ($user) {
                return [
                    'name' => substr($user->name, 0, 3) . '***',
                    'vouches' => $user->vouches_count,
                ];
            });

        return response()->json([
            'top_holders' => $topHolders,
            'top_referrers' => $topReferrers,
            'top_wingmen' => $topWingmen,
            'top_vouched' => $topVouched,
        ]);
    }
}
