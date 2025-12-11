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
            $result = $this->avatarService->generateAvatar($user, $request->all());

            if ($result['success']) {
                // Extract relative path from URL
                // URL is like http://localhost/storage/avatars/uuid.png
                // Path should be avatars/uuid.png
                $path = 'avatars/' . basename($result['image_url']);

                // Save to photos table
                $photo = new Photo();
                $photo->user_id = $user->id;
                $photo->file_path = $path;
                $photo->filename = basename($path);
                $photo->original_filename = 'ai-generated.png';
                $photo->mime_type = 'image/png';
                $photo->is_private = false;
                $photo->is_primary = false;
                $photo->metadata = [
                    'source' => 'ai',
                    'provider' => $result['provider'],
                    'model' => $request->input('model'),
                    'style' => $request->input('style'),
                ];
                $photo->save();

                return response()->json([
                    'success' => true,
                    'avatar_url' => $result['image_url'],
                    'photo_id' => $photo->id,
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
