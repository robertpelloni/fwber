<?php

namespace App\Http\Controllers;

use App\Models\Photo;
use App\Models\User;
use App\Models\UserMatch;
use App\Services\MediaAnalysis\MediaAnalysisInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager;

class PhotoController extends Controller
{
    protected MediaAnalysisInterface $mediaAnalysis;

    public function __construct(MediaAnalysisInterface $mediaAnalysis)
    {
        $this->mediaAnalysis = $mediaAnalysis;
    }

    private const MAX_PHOTOS_PER_USER = 10;

    /**
     * List user photos.
     */
    public function index(Request $request): JsonResponse
    {
        $user = auth()->user();
        $photos = $user->photos()->orderBy('order', 'asc')->get();

        return response()->json([
            'success' => true,
            'data' => $photos,
            'count' => $photos->count(),
        ]);
    }

    /**
     * Upload a photo.
     */
    public function store(Request $request): JsonResponse
    {
        $user = auth()->user();
        
        $validator = Validator::make($request->all(), [
            'photo' => 'required|file|mimes:jpeg,png,gif,webp|max:5120',
            'is_private' => 'sometimes|boolean',
            'unlock_price' => 'nullable|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $file = $request->file('photo');
        $filename = Str::uuid().'.'.$file->getClientOriginalExtension();
        $filePath = 'photos/'.$user->id.'/'.$filename;

        // Image Processing
        try {
            $manager = new ImageManager(new Driver);
            $image = $manager->read($file->getRealPath());
            $image = $image->scaleDown(width: 1200, height: 1200);
            
            Storage::disk('public')->put($filePath, (string) $image->toJpeg(80));
        } catch (\Exception $e) {
            return response()->json(['message' => 'Image processing failed'], 422);
        }

        // Safety Analysis
        if (config('features.media_analysis')) {
            $analysis = $this->mediaAnalysis->analyze($filePath, 'image');
            if (!$analysis->safe) {
                Storage::disk('public')->delete($filePath);
                return response()->json(['message' => 'Photo rejected by safety filter'], 422);
            }
        }

        $photo = Photo::create([
            'user_id' => $user->id,
            'storage_path' => $filePath,
            'url' => Storage::disk('public')->url($filePath),
            'is_primary' => $user->photos()->count() === 0,
            'is_private' => $request->boolean('is_private'),
            'unlock_price' => $request->boolean('is_private') ? $request->input('unlock_price') : null,
            'order' => $user->photos()->count(),
        ]);

        return response()->json(['success' => true, 'data' => $photo], 201);
    }

    /**
     * Delete a photo.
     */
    public function destroy(int $id): JsonResponse
    {
        $user = auth()->user();
        $photo = $user->photos()->find($id);

        if (!$photo) return response()->json(['message' => 'Not found'], 404);

        Storage::disk('public')->delete($photo->storage_path);
        $photo->delete();

        return response()->json(['success' => true]);
    }

    /**
     * Get original photo file (protected by match status).
     */
    public function show(int $id): JsonResponse|\Symfony\Component\HttpFoundation\StreamedResponse
    {
        $user = auth()->user();
        $photo = Photo::findOrFail($id);

        if ($photo->user_id === $user->id) {
            return Storage::disk('public')->response($photo->storage_path);
        }

        // Must be matched and active to view original
        $matched = UserMatch::where(function($q) use ($user, $photo) {
            $q->where('user1_id', $user->id)->where('user2_id', $photo->user_id);
        })->orWhere(function($q) use ($user, $photo) {
            $q->where('user1_id', $photo->user_id)->where('user2_id', $user->id);
        })->where('is_active', true)->exists();

        if (!$matched) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        return Storage::disk('public')->response($photo->storage_path);
    }
}
