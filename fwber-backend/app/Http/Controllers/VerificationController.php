<?php

namespace App\Http\Controllers;

use App\Services\MediaAnalysis\MediaAnalysisInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;

class VerificationController extends Controller
{
    protected MediaAnalysisInterface $mediaAnalysis;

    public function __construct(MediaAnalysisInterface $mediaAnalysis)
    {
        $this->mediaAnalysis = $mediaAnalysis;
    }

    /**
     * Initiate verification by uploading a selfie
     * 
     * @OA\Post(
     *   path="/verification/verify",
     *   tags={"Verification"},
     *   summary="Verify user identity with selfie",
     *   security={{"bearerAuth":{}}},
     *   @OA\RequestBody(
     *     required=true,
     *     @OA\MediaType(
     *       mediaType="multipart/form-data",
     *       @OA\Schema(
     *         required={"photo"},
     *         @OA\Property(property="photo", type="string", format="binary", description="Selfie image")
     *       )
     *     )
     *   ),
     *   @OA\Response(response=200, description="Verification result"),
     *   @OA\Response(response=422, ref="#/components/schemas/ValidationError")
     * )
     */
    public function verify(Request $request): JsonResponse
    {
        try {
            $user = auth()->user();
            
            if ($user->profile && $user->profile->is_verified) {
                return response()->json(['message' => 'User already verified', 'verified' => true]);
            }

            $validator = Validator::make($request->all(), [
                'photo' => 'required|file|mimes:jpeg,png,webp|max:5120',
            ]);

            if ($validator->fails()) {
                return response()->json(['errors' => $validator->errors()], 422);
            }

            // Get primary profile photo
            $primaryPhoto = $user->photos()->where('is_primary', true)->first();
            
            if (!$primaryPhoto) {
                return response()->json(['message' => 'Please upload a profile photo first'], 422);
            }

            // Process uploaded selfie
            $file = $request->file('photo');
            $filename = 'verification_' . Str::uuid() . '.' . $file->getClientOriginalExtension();
            $path = 'verification/' . $user->id . '/' . $filename;

            // Resize to reasonable size for analysis
            $manager = new ImageManager(new Driver());
            $image = $manager->read($file->getRealPath());
            $image->scaleDown(width: 1000, height: 1000);
            
            $extension = $file->getClientOriginalExtension();
            $encoded = match(strtolower($extension)) {
                'png' => $image->toPng(),
                'webp' => $image->toWebp(),
                'gif' => $image->toGif(),
                default => $image->toJpeg(80),
            };
            
            Storage::disk('public')->put($path, (string) $encoded);

            // Compare faces
            try {
                $similarity = $this->mediaAnalysis->compareFaces($path, $primaryPhoto->file_path);
            } catch (\Exception $e) {
                // Clean up on error
                Storage::disk('public')->delete($path);
                throw $e;
            }

            $threshold = 80.0;
            $verified = $similarity >= $threshold;

            if ($verified) {
                // Update profile
                $user->profile()->update([
                    'is_verified' => true,
                    'verified_at' => now(),
                    'verification_photo_path' => $path
                ]);

                Log::info("User {$user->id} verified successfully. Similarity: {$similarity}%");
            } else {
                // Delete failed verification photo
                Storage::disk('public')->delete($path);
                Log::warning("User {$user->id} verification failed. Similarity: {$similarity}%");
            }

            return response()->json([
                'success' => true,
                'verified' => $verified,
                'similarity' => $similarity,
                'message' => $verified ? 'Verification successful!' : 'Verification failed. Face did not match profile photo.'
            ]);

        } catch (\Exception $e) {
            Log::error('Verification error', [
                'user_id' => auth()->id(),
                'error' => $e->getMessage()
            ]);
            return response()->json(['message' => 'Error processing verification'], 500);
        }
    }

    /**
     * Get verification status
     */
    public function status(Request $request): JsonResponse
    {
        $user = auth()->user();
        $profile = $user->profile;

        return response()->json([
            'is_verified' => $profile ? (bool)$profile->is_verified : false,
            'verified_at' => $profile ? $profile->verified_at : null,
        ]);
    }
}
