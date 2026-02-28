<?php

namespace App\Http\Controllers;

use App\Models\ScrapbookEntry;
use App\Models\UserMatch;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ScrapbookController extends Controller
{
    /**
     * List all scrapbook entries for a match pair.
     */
    public function index(Request $request, string $matchId): JsonResponse
    {
        $userId = Auth::id();

        // Verify they are matched
        if (!$this->isMatched($userId, $matchId)) {
            return response()->json(['error' => 'You are not matched with this user.'], 403);
        }

        $entries = ScrapbookEntry::forPair($userId, (int) $matchId)
            ->orderByDesc('is_pinned')
            ->orderByDesc('created_at')
            ->get()
            ->map(function ($entry) use ($userId) {
                $entry->is_mine = $entry->user_id === $userId;
                return $entry;
            });

        return response()->json([
            'entries' => $entries,
            'meta' => [
                'total' => $entries->count(),
                'pinned' => $entries->where('is_pinned', true)->count(),
            ],
        ]);
    }

    /**
     * Add a new scrapbook entry.
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'match_id' => 'required|exists:users,id',
            'type' => 'required|in:text,image,voice,link',
            'content' => 'required|string|max:2000',
            'media_url' => 'nullable|string|max:500',
            'media_type' => 'nullable|string|max:100',
            'emoji' => 'nullable|string|max:10',
            'color' => 'nullable|string|max:20',
        ]);

        $userId = Auth::id();
        $matchId = $request->input('match_id');

        if ($userId == $matchId) {
            return response()->json(['error' => 'Cannot create scrapbook with yourself.'], 422);
        }

        if (!$this->isMatched($userId, $matchId)) {
            return response()->json(['error' => 'You are not matched with this user.'], 403);
        }

        $entry = ScrapbookEntry::create([
            'user_id' => $userId,
            'match_user_id' => $matchId,
            'type' => $request->input('type'),
            'content' => $request->input('content'),
            'media_url' => $request->input('media_url'),
            'media_type' => $request->input('media_type'),
            'emoji' => $request->input('emoji'),
            'color' => $request->input('color'),
        ]);

        $entry->is_mine = true;

        return response()->json(['entry' => $entry], 201);
    }

    /**
     * Toggle pin on an entry.
     */
    public function togglePin(string $id): JsonResponse
    {
        $userId = Auth::id();
        $entry = ScrapbookEntry::findOrFail($id);

        // Only participants can pin
        if ($entry->user_id !== $userId && $entry->match_user_id !== $userId) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $entry->is_pinned = !$entry->is_pinned;
        $entry->save();

        return response()->json(['entry' => $entry]);
    }

    /**
     * Delete an entry (only the creator can delete).
     */
    public function destroy(string $id): JsonResponse
    {
        $userId = Auth::id();
        $entry = ScrapbookEntry::findOrFail($id);

        if ($entry->user_id !== $userId) {
            return response()->json(['error' => 'Only the creator can delete this entry.'], 403);
        }

        $entry->delete();

        return response()->json(['message' => 'Entry deleted.']);
    }

    /**
     * Check if two users are matched.
     */
    private function isMatched(int $userId, int|string $matchId): bool
    {
        return UserMatch::where(function ($q) use ($userId, $matchId) {
            $q->where('user1_id', $userId)->where('user2_id', $matchId);
        })->orWhere(function ($q) use ($userId, $matchId) {
            $q->where('user1_id', $matchId)->where('user2_id', $userId);
        })->exists();
    }
}
