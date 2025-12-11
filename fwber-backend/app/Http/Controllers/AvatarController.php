<?php

namespace App\Http\Controllers;

use App\Http\Requests\GenerateAvatarRequest;
use App\Services\AvatarGenerationService;
use App\Models\Photo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AvatarController extends Controller
{
    protected $avatarService;

    public function __construct(AvatarGenerationService $avatarService)
    {
        $this->avatarService = $avatarService;
    }

    public function providers()
    {
        return response()->json([
            'providers' => $this->avatarService->getProviders()
        ]);
    }

    public function generate(GenerateAvatarRequest $request)
    {
        $user = Auth::user();
        
        try {
            // Dispatch job for async generation
            \App\Jobs\GenerateAvatar::dispatch($user, $request->all());

            return response()->json([
                'success' => true,
                'message' => 'Avatar generation started. You will be notified when it is ready.',
                'status' => 'processing',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
