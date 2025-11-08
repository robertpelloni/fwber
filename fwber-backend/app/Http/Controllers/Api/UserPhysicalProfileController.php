<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\UserPhysicalProfile;
use App\Jobs\GenerateAvatar;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class UserPhysicalProfileController extends Controller
{
    public function show()
    {
        $profile = UserPhysicalProfile::where('user_id', Auth::id())->first();
        return response()->json(['data' => $profile]);
    }

    public function upsert(Request $request)
    {
        $data = $request->validate([
            'height_cm' => 'nullable|integer|min:80|max:250',
            'body_type' => 'nullable|string|max:50',
            'hair_color' => 'nullable|string|max:50',
            'eye_color' => 'nullable|string|max:50',
            'skin_tone' => 'nullable|string|max:50',
            'ethnicity' => 'nullable|string|max:50',
            'facial_hair' => 'nullable|string|max:50',
            'tattoos' => 'nullable|boolean',
            'piercings' => 'nullable|boolean',
            'dominant_hand' => 'nullable|string|in:left,right,ambi',
            'fitness_level' => 'nullable|string|in:low,average,fit,athletic',
            'clothing_style' => 'nullable|string|max:50',
            'avatar_prompt' => 'nullable|string|max:500',
        ]);

        $profile = UserPhysicalProfile::updateOrCreate(
            ['user_id' => Auth::id()],
            $data
        );

        return response()->json(['data' => $profile]);
    }

    public function requestAvatar()
    {
        $profile = UserPhysicalProfile::firstOrNew(['user_id' => Auth::id()]);
        if (!$profile->avatar_prompt) {
            return response()->json(['error' => 'Set avatar_prompt first'], 422);
        }
        $profile->avatar_status = 'requested';
        $profile->save();
        // Dispatch async generation job (queue driver 'sync' will process immediately in dev)
        GenerateAvatar::dispatch($profile->id);
        return response()->json(['data' => $profile]);
    }
}
