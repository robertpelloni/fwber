<?php

namespace App\Http\Controllers;

use App\Http\Requests\PhotoRevealRequest;
use App\Models\Photo;
use App\Models\PhotoReveal;
use App\Models\UserMatch;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;

class PhotoRevealController extends Controller
{
    public function reveal(PhotoRevealRequest $request, Photo $photo)
    {
        $user = Auth::user();

        // Verify match belongs to user
        $match = UserMatch::where('id', $request->match_id)
            ->where(function ($query) use ($user) {
                $query->where('user1_id', $user->id)
                      ->orWhere('user2_id', $user->id);
            })
            ->firstOrFail();

        // Check if the photo belongs to the OTHER user in the match
        // If I am user1, photo must belong to user2.
        // If I am user2, photo must belong to user1.
        // OR if I am the owner (user_id == photo->user_id), I can reveal it too (maybe to self? or just create the record)
        
        if ($photo->user_id !== $user->id) {
            // I am not the owner. Check if I am in the match with the owner.
            $isMatchWithPhotoOwner = ($match->user1_id === $user->id && $match->user2_id === $photo->user_id) ||
                                     ($match->user2_id === $user->id && $match->user1_id === $photo->user_id);

            if (!$isMatchWithPhotoOwner) {
                 return response()->json(['error' => 'Unauthorized: Photo does not belong to match partner'], 403);
            }
        }

        $reveal = PhotoReveal::firstOrCreate([
            'user_id' => $user->id,
            'match_id' => $match->id,
            'photo_id' => $photo->id,
        ], [
            'status' => 'active',
        ]);

        return response()->json($reveal);
    }

    public function original(Request $request, Photo $photo, \App\Services\PhotoEncryptionService $encryptionService)
    {
        $user = Auth::user();

        // Check if user is owner
        if ($photo->user_id === $user->id) {
             // Owner can always see original
        } else {
            // Check if revealed
            $hasAccess = PhotoReveal::where('photo_id', $photo->id)
                ->where('status', 'active')
                ->whereHas('match', function ($query) use ($user) {
                    $query->where('user1_id', $user->id)
                          ->orWhere('user2_id', $user->id);
                })
                ->exists();

            if (!$hasAccess) {
                return response()->json(['error' => 'Unauthorized'], 403);
            }
        }

        if (!$photo->original_path) {
             return response()->json(['error' => 'Original not found'], 404);
        }

        if ($photo->is_encrypted) {
            try {
                // Decrypt and stream the response
                $content = $encryptionService->decryptAndRetrieve($photo->original_path, 'local');
                
                return response()->streamDownload(function () use ($content) {
                    echo $content;
                }, $photo->original_filename ?? 'photo.jpg', [
                    'Content-Type' => $photo->mime_type ?? 'image/jpeg',
                ]);
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::error("Decryption error for photo {$photo->id}: " . $e->getMessage());
                return response()->json(['error' => 'Failed to decrypt photo'], 500);
            }
        }

        // Assuming original is stored in a private disk, e.g., 'local'
        if (!Storage::disk('local')->exists($photo->original_path)) {
             return response()->json(['error' => 'File not found'], 404);
        }

        return Storage::disk('local')->download($photo->original_path);
    }
}
