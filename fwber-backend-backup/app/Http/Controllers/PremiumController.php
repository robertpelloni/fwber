<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class PremiumController extends Controller
{
    public function getWhoLikesYou(Request $request)
    {
        $user = $request->user();
        
        // Get users who liked the current user
        $likerIds = DB::table('match_actions')
            ->where('target_user_id', $user->id)
            ->where('action', 'like')
            ->pluck('user_id');
            
        $likers = User::with(['profile', 'photos'])->whereIn('id', $likerIds)->get();

        return response()->json($likers);
    }

    public function purchasePremium(Request $request)
    {
        $user = $request->user();
        
        $user->tier = 'gold';
        $user->tier_expires_at = Carbon::now()->addDays(30);
        $user->unlimited_swipes = true;
        $user->save();

        return response()->json([
            'message' => 'Premium purchased successfully',
            'tier' => $user->tier,
            'expires_at' => $user->tier_expires_at
        ]);
    }

    public function getPremiumStatus(Request $request)
    {
        $user = $request->user();
        
        $isPremium = $user->tier === 'gold' && 
                     $user->tier_expires_at && 
                     Carbon::parse($user->tier_expires_at)->isFuture();

        return response()->json([
            'is_premium' => $isPremium,
            'tier' => $user->tier,
            'expires_at' => $user->tier_expires_at,
            'unlimited_swipes' => $user->unlimited_swipes
        ]);
    }
}
