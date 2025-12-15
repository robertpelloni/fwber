<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\TokenTransaction;

class TokenController extends Controller
{
    public function balance(Request $request)
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

    public function updateAddress(Request $request)
    {
        $request->validate([
            'wallet_address' => 'required|string|max:255', // Add regex for ETH/SOL if needed
        ]);

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

        return response()->json([
            'top_holders' => $topHolders,
            'top_referrers' => $topReferrers,
            'top_wingmen' => $topWingmen,
        ]);
    }
}
