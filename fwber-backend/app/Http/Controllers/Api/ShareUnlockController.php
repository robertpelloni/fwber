<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ShareUnlockController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'target_profile_id' => 'required|exists:users,id',
            'platform' => 'required|string',
        ]);

        $unlock = \App\Models\ShareUnlock::firstOrCreate(
            [
                'user_id' => $request->user()->id,
                'target_profile_id' => $request->target_profile_id,
            ],
            [
                'platform' => $request->platform,
            ]
        );

        return response()->json([
            'message' => 'Profile unlocked successfully',
            'unlocked' => true,
        ]);
    }

    public function check($targetProfileId)
    {
        $unlocked = \App\Models\ShareUnlock::where('user_id', auth()->id())
            ->where('target_profile_id', $targetProfileId)
            ->exists();

        return response()->json(['unlocked' => $unlocked]);
    }
}
