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

    /**
     * Generate avatar from an existing uploaded photo (img2img).
     */
    public function generateFromPhoto(Request $request)
    {
        $request->validate([
            'photo_id' => 'required|integer|exists:photos,id',
            'style' => 'nullable|string',
            'sexy_boost' => 'nullable|boolean',
        ]);

        $user = Auth::user();
        $photo = Photo::where('id', $request->photo_id)
            ->where('user_id', $user->id)
            ->firstOrFail();

        try {
            $options = $request->only(['style', 'sexy_boost', 'provider']);
            $options['source_photo_id'] = $photo->id;
            
            \App\Jobs\GenerateAvatar::dispatch($user, array_merge($options, [
                'from_photo' => true,
                'photo_path' => $photo->path ?? $photo->filename,
            ]));

            return response()->json([
                'success' => true,
                'message' => 'Generating sexy avatar from your photo...',
                'status' => 'processing',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Return the user's physical traits for auto-populating the avatar form.
     */
    public function physicalTraits()
    {
        $user = Auth::user();
        $profile = $user->profile;

        return response()->json([
            'traits' => [
                'age' => $profile?->birthdate ? $profile->birthdate->age : null,
                'gender' => $profile?->gender,
                'ethnicity' => $profile?->ethnicity,
                'body_type' => $profile?->body_type,
                'hair_color' => $profile?->hair_color,
                'eye_color' => $profile?->eye_color,
                'height_cm' => $profile?->height_cm,
                'skin_tone' => $profile?->skin_tone,
                'facial_hair' => $profile?->facial_hair,
                'breast_size' => $profile?->breast_size,
                'fitness_level' => $profile?->fitness_level,
            ],
            'has_photos' => $user->photos()->count() > 0,
            'photos' => $user->photos()->select('id', 'filename', 'path')->limit(6)->get(),
        ]);
    }
}
