<?php

namespace App\Http\Controllers;

use App\Models\ProximityArtifact;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class CatPhotoController extends Controller
{
    /**
     * List top rated cats.
     */
    public function index()
    {
        $cats = ProximityArtifact::active()
            ->where('type', 'cat_photo')
            ->get()
            ->map(function ($artifact) {
                return [
                    'id' => $artifact->id,
                    'user_name' => $artifact->user->name ?? 'Anonymous',
                    'image_url' => $artifact->meta['image_url'] ?? null,
                    'cat_name' => $artifact->meta['cat_name'] ?? 'Mystery Pussy',
                    'rating' => $artifact->meta['average_rating'] ?? 0,
                    'vote_count' => $artifact->votes()->count(),
                ];
            })
            ->sortByDesc('rating')
            ->values();

        return response()->json($cats);
    }

    /**
     * Upload a cat photo.
     */
    public function store(Request $request)
    {
        $request->validate([
            'image' => 'required|image|max:5120', // 5MB limit
            'cat_name' => 'required|string|max:50',
        ]);

        $user = Auth::user();
        $path = $request->file('image')->store('cats', 'public');

        $artifact = ProximityArtifact::create([
            'user_id' => $user->id,
            'type' => 'cat_photo',
            'content' => "Check out my cat: {$request->cat_name}",
            'location_lat' => $user->profile->latitude ?? 0,
            'location_lng' => $user->profile->longitude ?? 0,
            'visibility_radius_m' => 50000, // 50km visibility for global-ish feed
            'moderation_status' => 'pending',
            'meta' => [
                'image_url' => Storage::disk('public')->url($path),
                'cat_name' => $request->cat_name,
                'average_rating' => 0,
                'total_score' => 0,
                'rating_count' => 0,
            ],
            'expires_at' => now()->addDays(30),
        ]);

        return response()->json([
            'message' => 'Your pussy has been uploaded for judgment!',
            'cat' => $artifact,
        ], 201);
    }

    /**
     * Rate a cat photo.
     */
    public function rate(Request $request, $id)
    {
        $request->validate([
            'rating' => 'required|integer|min:1|max:10',
        ]);

        $artifact = ProximityArtifact::where('type', 'cat_photo')->findOrFail($id);
        $user = Auth::user();

        // Use existing vote system but with rating value in meta
        $vote = $artifact->votes()->updateOrCreate(
            ['user_id' => $user->id],
            ['vote_type' => 'up'] // We use 'up' as standard, actual rating in total_score
        );

        // Recalculate average in artifact meta
        $meta = $artifact->meta;
        $allVotes = $artifact->votes()->count();

        // This is a simplification, ideally we'd store individual ratings
        // but for a viral feature we'll just track score in meta for now
        $meta['rating_count'] = ($meta['rating_count'] ?? 0) + 1;
        $meta['total_score'] = ($meta['total_score'] ?? 0) + $request->rating;
        $meta['average_rating'] = round($meta['total_score'] / $meta['rating_count'], 1);

        $artifact->update(['meta' => $meta]);

        return response()->json([
            'message' => 'Rating submitted!',
            'average_rating' => $meta['average_rating'],
        ]);
    }
}
