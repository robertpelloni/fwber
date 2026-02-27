<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AvatarEmotionController extends Controller
{
    /**
     * Update the authenticated user's current avatar emotion.
     * Allowed emotional states: neutral, happy, flirty, mysterious, intense.
     */
    public function update(Request $request)
    {
        $request->validate([
            'emotion' => 'required|string|in:neutral,happy,flirty,mysterious,intense',
        ]);

        $profile = Auth::user()->profile;
        if (!$profile) {
            return response()->json(['message' => 'Profile not found'], 404);
        }

        $profile->current_emotion = $request->emotion;
        $profile->emotion_updated_at = now();
        $profile->save();

        return response()->json([
            'message' => 'Avatar emotion updated successfully',
            'current_emotion' => $profile->current_emotion,
            'emotion_updated_at' => $profile->emotion_updated_at
        ]);
    }
}
