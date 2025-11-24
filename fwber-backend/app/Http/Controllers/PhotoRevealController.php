<?php

namespace App\Http\Controllers;

use App\Models\Photo;
use App\Models\PhotoReveal;
use App\Models\UserMatch;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;

class PhotoRevealController extends Controller
{
    public function reveal(Request $request, Photo $photo)
    {
        $request->validate([
            'match_id' => 'required|exists:matches,id',
        ]);

        $user = Auth::user();

        if ($photo->user_id !== $user->id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        // Verify match belongs to user
        $match = UserMatch::where('id', $request->match_id)
            ->where(function ($query) use ($user) {
                $query->where('user1_id', $user->id)
                      ->orWhere('user2_id', $user->id);
            })
            ->firstOrFail();

        $reveal = PhotoReveal::firstOrCreate([
            'user_id' => $user->id,
            'match_id' => $match->id,
            'photo_id' => $photo->id,
        ], [
            'status' => 'active',
        ]);

        return response()->json($reveal);
    }

    public function original(Request $request, Photo $photo)
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
            // TODO: Implement decryption logic
            return response()->json(['error' => 'Encryption not supported in MVP yet'], 501);
        }

        // Assuming original is stored in a private disk, e.g., 'local'
        if (!Storage::disk('local')->exists($photo->original_path)) {
             return response()->json(['error' => 'File not found'], 404);
        }

        return Storage::disk('local')->download($photo->original_path);
    }
}
