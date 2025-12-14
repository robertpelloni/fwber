<?php

namespace App\Http\Controllers;

use App\Models\Photo;
use App\Models\PhotoUnlock;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PhotoUnlockController extends Controller
{
    public function unlock(Request $request, $photoId)
    {
        $user = $request->user();
        $photo = Photo::findOrFail($photoId);
        $cost = 50; // Standard unlock cost

        if ($photo->user_id === $user->id) {
            return response()->json(['message' => 'You own this photo'], 400);
        }

        // Check if already unlocked
        if (PhotoUnlock::where('user_id', $user->id)->where('photo_id', $photoId)->exists()) {
            return response()->json(['success' => true, 'message' => 'Already unlocked']);
        }

        if ($user->token_balance < $cost) {
            return response()->json([
                'error' => 'Insufficient tokens',
                'required' => $cost,
                'balance' => $user->token_balance
            ], 402);
        }

        try {
            DB::transaction(function () use ($user, $photo, $cost) {
                // Deduct tokens
                $user->decrement('token_balance', $cost);
                
                // Create unlock record
                PhotoUnlock::create([
                    'user_id' => $user->id,
                    'photo_id' => $photo->id,
                    'cost' => $cost
                ]);

                // Reward the photo owner (Incentive for uploading content)
                // They get 10% of the unlock cost
                $photo->user->increment('token_balance', intval($cost * 0.1));
            });

            return response()->json([
                'success' => true, 
                'message' => 'Photo unlocked!',
                'balance' => $user->fresh()->token_balance
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Transaction failed'], 500);
        }
    }
}
