<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\UserMatch;
use App\Models\RelationshipTier;
use App\Models\Photo;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class RelationshipTierController extends Controller
{
    /**
     * Verify user is authorized to access this match and return the match with tier
     */
    private function authorizeAndLoadMatch(int $matchId): UserMatch
    {
        $match = UserMatch::with('relationshipTier')->findOrFail($matchId);

        $userId = Auth::id();
        if ($match->user1_id !== $userId && $match->user2_id !== $userId) {
            abort(403, 'Unauthorized');
        }

        return $match;
    }

    /**
     * Get or create tier for a match
     */
    private function getOrCreateTier(UserMatch $match): RelationshipTier
    {
        return $match->relationshipTier ?? RelationshipTier::create([
            'match_id' => $match->id,
            'current_tier' => 'matched',
            'first_matched_at' => $match->created_at,
        ]);
    }

    /**
     * Get tier progress for a specific match
     */
    public function show(int $matchId): JsonResponse
    {
        $match = $this->authorizeAndLoadMatch($matchId);
        $tier = $this->getOrCreateTier($match);

        // Update days connected
        $tier->updateDaysConnected();

        return response()->json([
            'match_id' => $matchId,
            'current_tier' => $tier->current_tier,
            'messages_exchanged' => $tier->messages_exchanged,
            'days_connected' => $tier->days_connected,
            'has_met_in_person' => $tier->has_met_in_person,
            'tier_info' => $tier->getTierInfo(),
            'created_at' => $tier->created_at,
            'updated_at' => $tier->updated_at,
        ]);
    }

    /**
     * Update tier metrics
     */
    public function update(Request $request, int $matchId): JsonResponse
    {
        $match = $this->authorizeAndLoadMatch($matchId);
        $tier = $this->getOrCreateTier($match);

        $validated = $request->validate([
            'increment_messages' => 'sometimes|boolean',
            'mark_met_in_person' => 'sometimes|boolean',
        ]);

        $previousTier = $tier->current_tier;

        if ($validated['increment_messages'] ?? false) {
            $tier->incrementMessages();
        }

        if ($validated['mark_met_in_person'] ?? false) {
            $tier->markMetInPerson();
        }

        $tierUpgraded = $tier->current_tier !== $previousTier;

        return response()->json([
            'match_id' => $matchId,
            'current_tier' => $tier->current_tier,
            'previous_tier' => $previousTier,
            'tier_upgraded' => $tierUpgraded,
            'messages_exchanged' => $tier->messages_exchanged,
            'days_connected' => $tier->days_connected,
            'has_met_in_person' => $tier->has_met_in_person,
            'tier_info' => $tier->getTierInfo(),
        ]);
    }

    /**
     * Get photos for a match based on current tier
     */
    public function getPhotos(int $matchId): JsonResponse
    {
        $match = $this->authorizeAndLoadMatch($matchId);
        $tier = $this->getOrCreateTier($match);

        // Get the other user's photos
        $userId = Auth::id();
        $otherUser = $match->getOtherUser($userId);
        
        $aiPhotos = Photo::where('user_id', $otherUser->id)
            ->where('photo_type', 'ai')
            ->orderBy('sort_order')
            ->get();

        $realPhotos = Photo::where('user_id', $otherUser->id)
            ->where('photo_type', 'real')
            ->orderBy('sort_order')
            ->get();

        $photoCounts = $tier->getVisiblePhotoCount($realPhotos->count());

        return response()->json([
            'match_id' => $matchId,
            'current_tier' => $tier->current_tier,
            'ai_photos' => $aiPhotos->map(fn($photo) => [
                'id' => $photo->id,
                'url' => asset('storage/' . $photo->file_path),
                'thumbnail_url' => $photo->thumbnail_path ? asset('storage/' . $photo->thumbnail_path) : null,
                'type' => 'ai',
                'is_primary' => $photo->is_primary,
            ]),
            'real_photos' => [
                'visible' => $realPhotos->take($photoCounts['real'])->map(fn($photo) => [
                    'id' => $photo->id,
                    'url' => asset('storage/' . $photo->file_path),
                    'thumbnail_url' => $photo->thumbnail_path ? asset('storage/' . $photo->thumbnail_path) : null,
                    'type' => 'real',
                    'is_primary' => $photo->is_primary,
                    'blurred' => false,
                ]),
                'blurred' => $realPhotos->skip($photoCounts['real'])
                    ->take($photoCounts['blurred'])
                    ->map(fn($photo) => [
                        'id' => $photo->id,
                        'url' => asset('storage/' . $photo->file_path),
                        'thumbnail_url' => $photo->thumbnail_path ? asset('storage/' . $photo->thumbnail_path) : null,
                        'type' => 'real',
                        'is_primary' => $photo->is_primary,
                        'blurred' => true,
                    ]),
                'locked' => max(0, $realPhotos->count() - $photoCounts['real'] - $photoCounts['blurred']),
            ],
            'unlock_requirements' => $this->getUnlockRequirements($tier),
        ]);
    }

    /**
     * Get requirements to unlock next tier
     */
    private function getUnlockRequirements(RelationshipTier $tier): array
    {
        switch ($tier->current_tier) {
            case 'discovery':
            case 'matched':
                return [
                    'next_tier' => 'connected',
                    'requirements' => [
                        ['description' => 'Exchange at least 10 messages', 'met' => $tier->messages_exchanged >= 10],
                        ['description' => 'Stay connected for 1+ days', 'met' => $tier->days_connected >= 1],
                    ]
                ];

            case 'connected':
                return [
                    'next_tier' => 'established',
                    'requirements' => [
                        ['description' => 'Exchange at least 50 messages', 'met' => $tier->messages_exchanged >= 50],
                        ['description' => 'Stay connected for 7+ days', 'met' => $tier->days_connected >= 7],
                    ]
                ];

            case 'established':
                return [
                    'next_tier' => 'verified',
                    'requirements' => [
                        ['description' => 'Meet in person', 'met' => $tier->has_met_in_person],
                        ['description' => 'Both confirm meeting', 'met' => $tier->has_met_in_person],
                    ]
                ];

            case 'verified':
                return [
                    'next_tier' => null,
                    'requirements' => []
                ];

            default:
                return ['next_tier' => null, 'requirements' => []];
        }
    }
}
