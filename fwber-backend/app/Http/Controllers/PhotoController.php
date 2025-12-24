<?php

namespace App\Http\Controllers;

use App\Models\CreatorSubscription;
use App\Models\Photo;
use App\Models\PhotoUnlock;
use App\Models\CreatorSubscription;
use App\Models\TokenTransaction;
use App\Models\User;
use App\Notifications\PhotoUnlockedNotification;
use App\Services\MediaAnalysis\MediaAnalysisInterface;
use App\Services\TelemetryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;

/**
 * Photo Controller - User Photo Management API
 * 
 * AI Model: Claude 4.5 - Multi-AI Orchestration
 * Phase: 4A - Laravel Photo Upload System
 * Purpose: Provide RESTful API for user photo operations
 * 
 * Created: 2025-01-19
 * Multi-AI: Serena analysis + Chroma knowledge + Sequential thinking
 * Features: Upload, delete, set primary, privacy controls, image processing
 */
class PhotoController extends Controller
{
    protected MediaAnalysisInterface $mediaAnalysis;

    public function __construct(MediaAnalysisInterface $mediaAnalysis)
    {
        $this->mediaAnalysis = $mediaAnalysis;
    }

    /**
     * Maximum file size in bytes (5MB)
     */
    private const MAX_FILE_SIZE = 5 * 1024 * 1024;
    
    /**
     * Allowed MIME types
     */
    private const ALLOWED_MIME_TYPES = [
        'image/jpeg',
        'image/png',
        'image/gif',
        'image/webp',
    ];
    
    /**
     * Maximum number of photos per user
     */
    private const MAX_PHOTOS_PER_USER = 10;

    /**
     * Get authenticated user's photos
     * 
     * @OA\Get(
     *   path="/photos",
     *   tags={"Photos"},
     *   summary="List user photos",
     *   security={{"bearerAuth":{}}},
     *   @OA\Response(
     *     response=200,
     *     description="User photos",
     *     @OA\JsonContent(
     *       @OA\Property(property="success", type="boolean", example=true),
     *       @OA\Property(property="count", type="integer"),
     *       @OA\Property(property="max_photos", type="integer", example=10),
     *       @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/Photo"))
     *     )
     *   ),
     *   @OA\Response(response=401, ref="#/components/responses/Unauthorized")
     * )
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $user = auth()->user();
            
            if (!$user) {
                return response()->json(['message' => 'Unauthenticated'], 401);
            }
            
            $photos = $user->photos()
                ->ordered()
                ->get()
                ->map(function ($photo) {
                    return [
                        'id' => $photo->id,
                        'filename' => $photo->filename,
                        'original_filename' => $photo->original_filename,
                        'url' => $photo->url,
                        'thumbnail_url' => $photo->thumbnail_url,
                        'mime_type' => $photo->mime_type,
                        'file_size' => $photo->file_size,
                        'width' => $photo->width,
                        'height' => $photo->height,
                        'is_primary' => $photo->is_primary,
                        'is_private' => $photo->is_private,
                        'unlock_price' => $photo->unlock_price,
                        'sort_order' => $photo->sort_order,
                        'created_at' => $photo->created_at,
                        'updated_at' => $photo->updated_at,
                    ];
                });
            
            return response()->json([
                'success' => true,
                'data' => $photos,
                'count' => $photos->count(),
                'max_photos' => self::MAX_PHOTOS_PER_USER,
            ]);
            
        } catch (\Exception $e) {
            Log::error('Photos fetch error', [
                'user_id' => auth()->id(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            return response()->json([
                'message' => 'Error fetching photos',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    /**
     * Upload a new photo
     * 
     * @OA\Post(
     *   path="/photos",
     *   tags={"Photos"},
     *   summary="Upload photo",
     *   security={{"bearerAuth":{}}},
     *   @OA\RequestBody(
     *     required=true,
     *     @OA\MediaType(
     *       mediaType="multipart/form-data",
     *       @OA\Schema(
     *         required={"photo"},
     *         @OA\Property(property="photo", type="string", format="binary", description="Image file (JPEG, PNG, GIF, WebP, max 5MB)"),
     *         @OA\Property(property="is_private", type="boolean")
     *       )
     *     )
     *   ),
     *   @OA\Response(response=201, description="Photo uploaded"),
    *   @OA\Response(response=403, ref="#/components/responses/Forbidden"),
     *   @OA\Response(response=422, ref="#/components/schemas/ValidationError")
     * )
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $user = auth()->user();
            
            if (!$user) {
                return response()->json(['message' => 'Unauthenticated'], 401);
            }
            
            // Enforce generated-avatars-only mode for MVP
            if (config('app.avatar_mode', 'generated-only') === 'generated-only') {
                return response()->json([
                    'message' => 'Photo uploads disabled. Using generated avatars only.',
                    'avatar_mode' => 'generated-only',
                ], 403);
            }
            
            // Check photo limit
            $currentPhotoCount = $user->photos()->count();
            if ($currentPhotoCount >= self::MAX_PHOTOS_PER_USER) {
                return response()->json([
                    'message' => 'Maximum number of photos reached',
                    'max_photos' => self::MAX_PHOTOS_PER_USER,
                ], 422);
            }
            
            // Validate the uploaded file
            $validator = Validator::make($request->all(), [
                'photo' => 'required|file|mimes:jpeg,png,gif,webp|max:5120', // 5MB max
                'is_private' => 'sometimes|boolean',
                'unlock_price' => 'nullable|numeric|min:0',
            ]);
            
            if ($validator->fails()) {
                return response()->json([
                    'message' => 'Validation failed',
                    'errors' => $validator->errors(),
                ], 422);
            }
            
            $file = $request->file('photo');
            $faceBlurMetadata = $this->parseFaceBlurMetadata($request->input('face_blur_metadata'));
            
            // Additional validation
            if (!in_array($file->getMimeType(), self::ALLOWED_MIME_TYPES)) {
                return response()->json([
                    'message' => 'Invalid file type. Only JPEG, PNG, GIF, and WebP are allowed.',
                ], 422);
            }
            
            if ($file->getSize() > self::MAX_FILE_SIZE) {
                return response()->json([
                    'message' => 'File too large. Maximum size is 5MB.',
                ], 422);
            }
            
            // Generate unique filename
            $extension = $file->getClientOriginalExtension();
            $filename = Str::uuid() . '.' . $extension;
            $originalFilename = $file->getClientOriginalName();
            
            // Get image dimensions and create manager
            $manager = new ImageManager(new Driver());
            $image = $manager->read($file->getRealPath());
            // Downscale very large originals to reduce memory usage
            $image = $image->scaleDown(width: 2000, height: 2000);
            $width = $image->width();
            $height = $image->height();
            
            // Store the original image
            $filePath = 'photos/' . $user->id . '/' . $filename;
            Storage::disk('public')->put($filePath, (string) $image->encode());
            
            // Create thumbnail
            $thumbnailFilename = 'thumb_' . $filename;
            $thumbnailPath = 'photos/' . $user->id . '/thumbnails/' . $thumbnailFilename;
            
            // Scale down to 300x300 max while maintaining aspect ratio
            $thumbnail = $image->scaleDown(width: 300, height: 300);
            
            Storage::disk('public')->put($thumbnailPath, (string) $thumbnail->encode());
            
            // Analyze photo if feature is enabled
            $analysisMetadata = [];
            if (config('features.media_analysis')) {
                // Use the full URL or path depending on what the service expects.
                // The mock service uses the string to generate hash, so path is fine.
                $analysisResult = $this->mediaAnalysis->analyze($filePath, 'image');
                
                if (!$analysisResult->safe) {
                    // Delete the file we just uploaded
                    Storage::disk('public')->delete($filePath);
                    Storage::disk('public')->delete($thumbnailPath);
                    
                    return response()->json([
                        'message' => 'Photo rejected by safety filter',
                        'errors' => ['photo' => $analysisResult->moderationLabels]
                    ], 422);
                }
                
                $analysisMetadata = $analysisResult->toArray();
            }

            // Create photo record
            $photo = Photo::create([
                'user_id' => $user->id,
                'filename' => $filename,
                'original_filename' => $originalFilename,
                'file_path' => $filePath,
                'thumbnail_path' => $thumbnailPath,
                'mime_type' => $file->getMimeType(),
                'file_size' => $file->getSize(),
                'width' => $width,
                'height' => $height,
                'is_primary' => $currentPhotoCount === 0, // First photo is primary
                'is_private' => $request->boolean('is_private', false),
                'unlock_price' => $request->input('unlock_price'),
                'sort_order' => $currentPhotoCount,
                'metadata' => [
                    'uploaded_at' => now()->toISOString(),
                    'user_agent' => $request->userAgent(),
                    'analysis' => $analysisMetadata,
                ],
            ]);
            
            $this->emitFaceBlurTelemetry($user, $filename, $originalFilename, $faceBlurMetadata);

            Log::info('Photo uploaded', [
                'user_id' => $user->id,
                'photo_id' => $photo->id,
                'filename' => $filename,
                'file_size' => $file->getSize(),
            ]);
            
            return response()->json([
                'success' => true,
                'message' => 'Photo uploaded successfully',
                'data' => [
                    'id' => $photo->id,
                    'filename' => $photo->filename,
                    'original_filename' => $photo->original_filename,
                    'url' => $photo->url,
                    'thumbnail_url' => $photo->thumbnail_url,
                    'mime_type' => $photo->mime_type,
                    'file_size' => $photo->file_size,
                    'width' => $photo->width,
                    'height' => $photo->height,
                    'is_primary' => $photo->is_primary,
                    'is_private' => $photo->is_private,
                    'unlock_price' => $photo->unlock_price,
                    'sort_order' => $photo->sort_order,
                    'created_at' => $photo->created_at,
                ],
            ], 201);
            
        } catch (\Exception $e) {
            Log::error('Photo upload error', [
                'user_id' => auth()->id(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            return response()->json([
                'message' => 'Error uploading photo',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    /**
     * Update photo properties (privacy, primary status)
     * 
     * @OA\Put(
     *   path="/photos/{id}",
     *   tags={"Photos"},
     *   summary="Update photo",
     *   security={{"bearerAuth":{}}},
     *   @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *   @OA\RequestBody(
     *     required=true,
     *     @OA\JsonContent(
     *       @OA\Property(property="is_primary", type="boolean"),
     *       @OA\Property(property="is_private", type="boolean"),
     *       @OA\Property(property="sort_order", type="integer", minimum=0)
     *     )
     *   ),
     *   @OA\Response(response=200, description="Photo updated"),
    *   @OA\Response(response=404, ref="#/components/responses/NotFound"),
     *   @OA\Response(response=422, ref="#/components/schemas/ValidationError")
     * )
     * 
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function update(Request $request, int $id): JsonResponse
    {
        try {
            $user = auth()->user();
            
            if (!$user) {
                return response()->json(['message' => 'Unauthenticated'], 401);
            }
            
            $photo = $user->photos()->find($id);
            
            if (!$photo) {
                return response()->json(['message' => 'Photo not found'], 404);
            }
            
            $validator = Validator::make($request->all(), [
                'is_primary' => 'sometimes|boolean',
                'is_private' => 'sometimes|boolean',
                'unlock_price' => 'nullable|numeric|min:0',
                'sort_order' => 'sometimes|integer|min:0',
            ]);
            
            if ($validator->fails()) {
                return response()->json([
                    'message' => 'Validation failed',
                    'errors' => $validator->errors(),
                ], 422);
            }
            
            // Handle primary photo setting
            if ($request->has('is_primary') && $request->boolean('is_primary')) {
                $photo->setAsPrimary();
            } else {
                $photo->update($request->only(['is_private', 'unlock_price', 'sort_order']));
            }
            
            Log::info('Photo updated', [
                'user_id' => $user->id,
                'photo_id' => $photo->id,
                'updated_fields' => array_keys($request->all()),
            ]);
            
            return response()->json([
                'success' => true,
                'message' => 'Photo updated successfully',
                'data' => [
                    'id' => $photo->id,
                    'filename' => $photo->filename,
                    'original_filename' => $photo->original_filename,
                    'url' => $photo->url,
                    'thumbnail_url' => $photo->thumbnail_url,
                    'mime_type' => $photo->mime_type,
                    'file_size' => $photo->file_size,
                    'width' => $photo->width,
                    'height' => $photo->height,
                    'is_primary' => $photo->is_primary,
                    'is_private' => $photo->is_private,
                    'unlock_price' => $photo->unlock_price,
                    'sort_order' => $photo->sort_order,
                    'updated_at' => $photo->updated_at,
                ],
            ]);
            
        } catch (\Exception $e) {
            Log::error('Photo update error', [
                'user_id' => auth()->id(),
                'photo_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            return response()->json([
                'message' => 'Error updating photo',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    /**
     * Delete a photo
     * 
     * @OA\Delete(
     *   path="/photos/{id}",
     *   tags={"Photos"},
     *   summary="Delete photo",
     *   security={{"bearerAuth":{}}},
     *   @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *   @OA\Response(response=200, description="Photo deleted"),
    *   @OA\Response(response=404, ref="#/components/responses/NotFound")
     * )
     * 
     * @param int $id
     * @return JsonResponse
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $user = auth()->user();
            
            if (!$user) {
                return response()->json(['message' => 'Unauthenticated'], 401);
            }
            
            $photo = $user->photos()->find($id);
            
            if (!$photo) {
                return response()->json(['message' => 'Photo not found'], 404);
            }
            
            $wasPrimary = $photo->is_primary;
            
            // Delete the photo (this will also delete files due to model events)
            $photo->delete();
            
            // If this was the primary photo, set another photo as primary
            if ($wasPrimary) {
                $nextPhoto = $user->photos()->first();
                if ($nextPhoto) {
                    $nextPhoto->setAsPrimary();
                }
            }
            
            Log::info('Photo deleted', [
                'user_id' => $user->id,
                'photo_id' => $id,
                'was_primary' => $wasPrimary,
            ]);
            
            return response()->json([
                'success' => true,
                'message' => 'Photo deleted successfully',
            ]);
            
        } catch (\Exception $e) {
            Log::error('Photo delete error', [
                'user_id' => auth()->id(),
                'photo_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            return response()->json([
                'message' => 'Error deleting photo',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    /**
     * Reorder photos
     * 
     * @OA\Post(
     *   path="/photos/reorder",
     *   tags={"Photos"},
     *   summary="Reorder photos",
     *   security={{"bearerAuth":{}}},
     *   @OA\RequestBody(
     *     required=true,
     *     @OA\JsonContent(
     *       required={"photo_ids"},
     *       @OA\Property(property="photo_ids", type="array", @OA\Items(type="integer"), description="Ordered array of photo IDs")
     *     )
     *   ),
     *   @OA\Response(response=200, description="Photos reordered"),
     *   @OA\Response(response=422, ref="#/components/schemas/ValidationError")
     * )
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function reorder(Request $request): JsonResponse
    {
        try {
            $user = auth()->user();
            
            if (!$user) {
                return response()->json(['message' => 'Unauthenticated'], 401);
            }
            
            $validator = Validator::make($request->all(), [
                'photo_ids' => 'required|array',
                "photo_ids.*" => 'integer|exists:photos,id',
            ]);
            
            if ($validator->fails()) {
                return response()->json([
                    'message' => 'Validation failed',
                    'errors' => $validator->errors(),
                ], 422);
            }
            
            $photoIds = $request->photo_ids;
            
            // Verify all photos belong to the user
            $userPhotoIds = $user->photos()->pluck('id')->toArray();
            if (count(array_diff($photoIds, $userPhotoIds)) > 0) {
                return response()->json(['message' => 'Invalid photo IDs'], 422);
            }
            
            // Update sort order
            foreach ($photoIds as $index => $photoId) {
                $user->photos()->where('id', $photoId)->update(['sort_order' => $index]);
            }
            
            Log::info('Photos reordered', [
                'user_id' => $user->id,
                'photo_ids' => $photoIds,
            ]);
            
            return response()->json([
                'success' => true,
                'message' => 'Photos reordered successfully',
            ]);
            
        } catch (\Exception $e) {
            Log::error('Photo reorder error', [
                'user_id' => auth()->id(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            return response()->json([
                'message' => 'Error reordering photos',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    /**
     * Reveal a photo to a match
     * 
     * @OA\Post(
     *   path="/photos/{id}/reveal",
     *   tags={"Photos"},
     *   summary="Reveal photo to match",
     *   security={{"bearerAuth":{}}},
     *   @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *   @OA\RequestBody(
     *     required=true,
     *     @OA\JsonContent(
     *       required={"match_id"},
     *       @OA\Property(property="match_id", type="string", description="Match ID")
     *     )
     *   ),
     *   @OA\Response(response=200, description="Photo revealed"),
     *   @OA\Response(response=403, ref="#/components/responses/Forbidden"),
     *   @OA\Response(response=404, ref="#/components/responses/NotFound")
     * )
     */
    public function reveal(Request $request, int $id): JsonResponse
    {
        try {
            $user = auth()->user();
            $photo = Photo::findOrFail($id);
            
            // Check if user is the owner (no need to reveal)
            if ($photo->user_id === $user->id) {
                return response()->json(['success' => true, 'status' => 'owner']);
            }

            // Check if photo is already unlocked via tokens
            if (PhotoUnlock::where('user_id', $user->id)->where('photo_id', $photo->id)->exists()) {
                return response()->json(['success' => true, 'status' => 'unlocked_via_tokens']);
            }

            // Check Creator Subscription
            if (CreatorSubscription::where('user_id', $user->id)->where('creator_id', $photo->user_id)->active()->exists()) {
                return response()->json(['success' => true, 'status' => 'subscriber_access']);
            }

            $matchId = $request->input('match_id');
            $match = \App\Models\UserMatch::where('id', $matchId)
                ->where(function($q) use ($user) {
                    $q->where('user_id_1', $user->id)
                      ->orWhere('user_id_2', $user->id);
                })
                ->first();

            if (!$match) {
                return response()->json(['message' => 'Match not found or unauthorized'], 403);
            }

            // Check Relationship Tier
            $tier = \App\Models\RelationshipTier::where('match_id', $match->id)->first();
            
            if (!$tier) {
                // Fallback if no tier record exists yet
                return response()->json(['message' => 'Relationship tier not found'], 403);
            }

            // Logic: Only allow reveal if tier is 'connected' or higher
            // 'matched' tier only allows blurred photos
            $allowedTiers = ['connected', 'established', 'verified'];
            
            if (!in_array($tier->current_tier, $allowedTiers)) {
                return response()->json([
                    'message' => 'Relationship tier too low to reveal photos. Keep chatting to unlock!',
                    'current_tier' => $tier->current_tier,
                    'required_tier' => 'connected'
                ], 403);
            }

            // Log the reveal event
            Log::info('Photo revealed', [
                'user_id' => $user->id,
                'photo_id' => $photo->id,
                'match_id' => $match->id,
                'tier' => $tier->current_tier
            ]);

            return response()->json([
                'success' => true, 
                'status' => 'revealed',
                'tier' => $tier->current_tier
            ]);

        } catch (\Exception $e) {
            Log::error('Photo reveal error', [
                'user_id' => auth()->id(),
                'photo_id' => $id,
                'error' => $e->getMessage()
            ]);
            return response()->json(['message' => 'Error revealing photo'], 500);
        }
    }

    /**
     * Unlock a private photo using tokens
     * 
     * @OA\Post(
     *   path="/photos/{id}/unlock",
     *   tags={"Photos"},
     *   summary="Unlock private photo",
     *   security={{"bearerAuth":{}}},
     *   @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *   @OA\Response(response=200, description="Photo unlocked"),
     *   @OA\Response(response=402, description="Insufficient tokens"),
     *   @OA\Response(response=404, ref="#/components/responses/NotFound")
     * )
     */
    public function unlock(int $id): JsonResponse
    {
        try {
            $user = auth()->user();
            $photo = Photo::findOrFail($id);
            
            // 1. Check if already unlocked or owner
            if ($photo->user_id === $user->id) {
                return response()->json(['success' => true, 'message' => 'You own this photo', 'balance' => $user->token_balance]);
            }

            if (PhotoUnlock::where('user_id', $user->id)->where('photo_id', $photo->id)->exists()) {
                return response()->json(['success' => true, 'message' => 'Already unlocked', 'balance' => $user->token_balance]);
            }

            // Check Creator Subscription
            if (CreatorSubscription::where('user_id', $user->id)->where('creator_id', $photo->user_id)->active()->exists()) {
                return response()->json(['success' => true, 'message' => 'Unlocked via Subscription', 'balance' => $user->token_balance]);
            }

            // 2. Determine Cost
            $cost = $photo->unlock_price ?? config('economy.photo_unlock_cost', 50);

            // 3. Check Balance Atomically
            if ($user->token_balance < $cost) {
                return response()->json([
                    'success' => false, 
                    'message' => 'Insufficient tokens. You need ' . $cost . ' tokens.',
                    'required' => $cost,
                    'balance' => $user->token_balance
                ], 402);
            }

            // 3. Process Transaction
            DB::beginTransaction();
            try {
                // Deduct tokens (Atomic Check & Update)
                $deducted = User::where('id', $user->id)
                    ->where('token_balance', '>=', $cost)
                    ->decrement('token_balance', $cost);

                if (!$deducted) {
                    throw new \Exception('Insufficient tokens (race condition).');
                }
                
                // Record spend transaction
                TokenTransaction::create([
                    'user_id' => $user->id,
                    'amount' => -$cost,
                    'type' => 'photo_unlock',
                    'description' => 'Unlocked private photo #' . $photo->id,
                    'metadata' => ['photo_id' => $photo->id, 'owner_id' => $photo->user_id]
                ]);

                // Credit Owner
                $photo->user->increment('token_balance', $cost);

                // Record earn transaction
                TokenTransaction::create([
                    'user_id' => $photo->user_id,
                    'amount' => $cost,
                    'type' => 'photo_earned',
                    'description' => 'Earned tokens from photo unlock',
                    'metadata' => ['photo_id' => $photo->id, 'viewer_id' => $user->id]
                ]);

                // Create unlock record
                PhotoUnlock::create([
                    'user_id' => $user->id,
                    'photo_id' => $photo->id,
                    'unlocked_at' => now(),
                    'cost' => $cost
                ]);

                // Notify owner
                $photo->user->notify(new PhotoUnlockedNotification($user, $photo));

                DB::commit();

                return response()->json([
                    'success' => true,
                    'message' => 'Photo unlocked successfully',
                    'balance' => $user->fresh()->token_balance
                ]);

            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }

        } catch (\Exception $e) {
            Log::error('Photo unlock error', [
                'user_id' => auth()->id(),
                'photo_id' => $id,
                'error' => $e->getMessage()
            ]);
            return response()->json(['message' => 'Error unlocking photo'], 500);
        }
    }

    /**
     * Get original photo file
     * 
     * @OA\Get(
     *   path="/photos/{id}/original",
     *   tags={"Photos"},
     *   summary="Get original photo",
     *   security={{"bearerAuth":{}}},
     *   @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *   @OA\Response(response=200, description="Original photo file"),
     *   @OA\Response(response=403, ref="#/components/responses/Forbidden"),
     *   @OA\Response(response=404, ref="#/components/responses/NotFound")
     * )
     */
    public function original(Request $request, int $id)
    {
        try {
            $user = auth()->user();
            $photo = Photo::findOrFail($id);

            // 1. Owner check
            if ($photo->user_id === $user->id) {
                return Storage::disk('public')->download($photo->file_path, $photo->original_filename);
            }

            // 2. Token Unlock Check
            if (PhotoUnlock::where('user_id', $user->id)->where('photo_id', $photo->id)->exists()) {
                return Storage::disk('public')->download($photo->file_path, $photo->original_filename);
            }

            // 3. Creator Subscription Check
            if (CreatorSubscription::where('user_id', $user->id)->where('creator_id', $photo->user_id)->active()->exists()) {
                return Storage::disk('public')->download($photo->file_path, $photo->original_filename);
            }

            // 3. Creator Subscription Check
            if (CreatorSubscription::where('user_id', $user->id)->where('creator_id', $photo->user_id)->active()->exists()) {
                return Storage::disk('public')->download($photo->file_path, $photo->original_filename);
            }

            // 4. Match check
            // Find any active match between these users
            $match = \App\Models\UserMatch::where(function($q) use ($user, $photo) {
                    $q->where('user_id_1', $user->id)->where('user_id_2', $photo->user_id);
                })
                ->orWhere(function($q) use ($user, $photo) {
                    $q->where('user_id_1', $photo->user_id)->where('user_id_2', $user->id);
                })
                ->first();

            if (!$match) {
                return response()->json(['message' => 'Unauthorized'], 403);
            }

            // 3. Tier check
            $tier = \App\Models\RelationshipTier::where('match_id', $match->id)->first();
            $allowedTiers = ['connected', 'established', 'verified'];

            if (!$tier || !in_array($tier->current_tier, $allowedTiers)) {
                 return response()->json(['message' => 'Photo locked by relationship tier'], 403);
            }

            // Return the file
            return Storage::disk('public')->download($photo->file_path, $photo->original_filename);

        } catch (\Exception $e) {
            Log::error('Photo original fetch error', [
                'user_id' => auth()->id(),
                'photo_id' => $id,
                'error' => $e->getMessage()
            ]);
            return response()->json(['message' => 'Error fetching photo'], 500);
        }
    }

    private function parseFaceBlurMetadata($metadata): ?array
    {
        if (!$metadata || !is_string($metadata)) {
            return null;
        }

        $decoded = json_decode($metadata, true);
        if (json_last_error() !== JSON_ERROR_NONE || !is_array($decoded)) {
            Log::warning('Invalid face blur metadata payload', [
                'metadata' => $metadata,
            ]);
            return null;
        }

        $allowedKeys = [
            'facesDetected',
            'blurApplied',
            'processingTimeMs',
            'skippedReason',
            'originalFileName',
            'processedFileName',
            'warningMessage',
            'previewId',
        ];

        $filtered = array_intersect_key($decoded, array_flip($allowedKeys));

        if (isset($filtered['facesDetected'])) {
            $filtered['facesDetected'] = (int) $filtered['facesDetected'];
        }

        if (isset($filtered['processingTimeMs'])) {
            $filtered['processingTimeMs'] = max(0, (int) $filtered['processingTimeMs']);
        }

        if (isset($filtered['blurApplied'])) {
            $filtered['blurApplied'] = (bool) $filtered['blurApplied'];
        }

        if (isset($filtered['skippedReason'])) {
            $filtered['skippedReason'] = (string) $filtered['skippedReason'];
        }

        if (isset($filtered['warningMessage'])) {
            $filtered['warningMessage'] = (string) $filtered['warningMessage'];
        }

        if (isset($filtered['originalFileName'])) {
            $filtered['originalFileName'] = (string) $filtered['originalFileName'];
        }

        if (isset($filtered['previewId'])) {
            $filtered['previewId'] = (string) $filtered['previewId'];
        }

        return $filtered;
    }

    private function emitFaceBlurTelemetry(User $user, string $storedFilename, string $originalFilename, ?array $metadata): void
    {
        if (!$metadata) {
            return;
        }

        /** @var TelemetryService $telemetry */
        $telemetry = app(TelemetryService::class);

        if (!empty($metadata['blurApplied'])) {
            $telemetry->emit('face_blur_applied', [
                'user_id' => $user->id,
                'photo_filename' => $storedFilename,
                'original_filename' => $metadata['originalFileName'] ?? $originalFilename,
                'faces_detected' => (int) ($metadata['facesDetected'] ?? 0),
                'processing_ms' => $metadata['processingTimeMs'] ?? null,
                'client_backend' => 'client',
                'warning' => $metadata['warningMessage'] ?? null,
                'preview_id' => $metadata['previewId'] ?? null,
            ]);
            return;
        }

        if (!empty($metadata['skippedReason'])) {
            $telemetry->emit('face_blur_skipped_reason', [
                'user_id' => $user->id,
                'photo_filename' => $storedFilename,
                'original_filename' => $metadata['originalFileName'] ?? $originalFilename,
                'reason' => (string) $metadata['skippedReason'],
                'faces_detected' => isset($metadata['facesDetected']) ? (int) $metadata['facesDetected'] : null,
                'warning' => $metadata['warningMessage'] ?? null,
                'preview_id' => $metadata['previewId'] ?? null,
            ]);
        }
    }
}
