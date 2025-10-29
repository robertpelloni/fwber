<?php

namespace App\Http\Controllers;

use App\Models\Photo;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Intervention\Image\Facades\Image;

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
            ]);
            
            if ($validator->fails()) {
                return response()->json([
                    'message' => 'Validation failed',
                    'errors' => $validator->errors(),
                ], 422);
            }
            
            $file = $request->file('photo');
            
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
            
            // Get image dimensions
            $image = Image::make($file);
            $width = $image->width();
            $height = $image->height();
            
            // Store the original image
            $filePath = 'photos/' . $user->id . '/' . $filename;
            Storage::put($filePath, $image->encode());
            
            // Create thumbnail
            $thumbnailFilename = 'thumb_' . $filename;
            $thumbnailPath = 'photos/' . $user->id . '/thumbnails/' . $thumbnailFilename;
            
            $thumbnail = $image->resize(300, 300, function ($constraint) {
                $constraint->aspectRatio();
                $constraint->upsize();
            });
            
            Storage::put($thumbnailPath, $thumbnail->encode());
            
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
                'sort_order' => $currentPhotoCount,
                'metadata' => [
                    'uploaded_at' => now()->toISOString(),
                    'user_agent' => $request->userAgent(),
                ],
            ]);
            
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
                $photo->update($request->only(['is_private', 'sort_order']));
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
                'photo_ids.*' => 'integer|exists:photos,id',
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
}
