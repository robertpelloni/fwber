<?php

namespace App\Http\Controllers;

use App\Services\AvatarGenerationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AvatarController extends Controller
{
    protected $avatarService;

    public function __construct(AvatarGenerationService $avatarService)
    {
        $this->avatarService = $avatarService;
    }

    public function generate(Request $request)
    {
        $request->validate([
            'style' => 'nullable|string|max:100',
            'provider' => 'nullable|string|in:dalle,gemini,replicate',
        ]);

        $user = Auth::user();
        
        try {
            $result = $this->avatarService->generateAvatar($user, $request->all());

            if ($result['success']) {
                $user->avatar_url = $result['image_url'];
                $user->save();

                return response()->json([
                    'success' => true,
                    'avatar_url' => $result['image_url'],
                    'provider' => $result['provider'],
                ]);
            }

            return response()->json([
                'success' => false,
                'error' => $result['error'] ?? 'Unknown error occurred',
            ], 500);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
