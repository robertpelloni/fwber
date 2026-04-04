<?php

namespace App\Http\Controllers;

use App\Domain\Core\EventSourcing\EventStore;
use App\Events\Matches\MatchActionRecorded;
use App\Http\Requests\MatchActionRequest;
use App\Http\Requests\MatchFilterRequest;
use App\Http\Resources\MatchResource;
use App\Models\Block;
use App\Models\User;
use App\Models\UserProfile;
use App\Services\AIMatchingService;
use App\Services\EmailNotificationService;
use App\Services\PushNotificationService;
use App\Support\TaggedCache;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class MatchController extends Controller
{
    private AIMatchingService $matchingService;
    private EmailNotificationService $emailService;
    private EventStore $eventStore;

    public function __construct(
        AIMatchingService $matchingService,
        EmailNotificationService $emailService,
        EventStore $eventStore
    ) {
        $this->matchingService = $matchingService;
        $this->emailService = $emailService;
        $this->eventStore = $eventStore;
    }

    /**
     * Get potential matches based on proximity and preferences.
     */
    public function index(MatchFilterRequest $request): JsonResponse
    {
        $user = auth()->user();
        $profile = $user->profile;

        if (! $profile) {
            return response()->json([
                'message' => 'Profile not found. Please complete your profile first.',
            ], 400);
        }

        $cacheKey = "feed:user_{$user->id}:".md5($request->getQueryString() ?? '');

        $matches = TaggedCache::remember(["matches_feed:user_{$user->id}"], $cacheKey, function () use ($user, $profile, $request) {
            $filters = [
                'age_min' => $request->get('age_min'),
                'age_max' => $request->get('age_max'),
                'max_distance' => $request->get('max_distance'),
                'interests' => $request->input('interests', []),
                'smoking' => $request->get('smoking'),
                'drinking' => $request->get('drinking'),
                'body_type' => $request->get('body_type'),
                'height_min' => $request->get('height_min'),
                'has_bio' => $request->get('has_bio'),
                'verified_only' => $request->get('verified_only'),
            ];

            $candidates = $this->matchingService->findAdvancedMatches($user, $filters);

            return collect($candidates)->map(function ($candidate) use ($profile) {
                $candidate->setAttribute('compatibility_score', $candidate->ai_score);
                $sharedInterests = $this->matchingService->getSharedInterests($profile, $candidate->profile);
                $candidate->setAttribute('shared_interests', $sharedInterests);
                $candidate->setAttribute('shared_interest_count', count($sharedInterests));
                
                $candidate->setAttribute(
                    'distance',
                    $this->calculateDistance($profile, $candidate->profile)
                );

                return $candidate;
            });
        }, 300);

        return response()->json([
            'matches' => MatchResource::collection($matches)->toArray(request()),
            'total' => $matches->count(),
        ]);
    }

    /**
     * Get users we have mutually matched with.
     */
    public function establishedMatches(Request $request): JsonResponse
    {
        $user = auth()->user();
        $cacheKey = "matches:established:user_{$user->id}";

        $conversations = TaggedCache::remember(["matches_list:user_{$user->id}"], $cacheKey, function () use ($user) {
            $matches = DB::table('user_matches')
                ->where('is_active', true)
                ->where(function ($query) use ($user) {
                    $query->where('user1_id', $user->id)
                        ->orWhere('user2_id', $user->id);
                })
                ->get();

            $blockedUserIds = Block::relatedBlockedUserIds($user->id);

            $userIds = $matches->map(function ($match) use ($user) {
                return $match->user1_id === $user->id ? $match->user2_id : $match->user1_id;
            })->reject(function ($id) use ($blockedUserIds) {
                return in_array($id, $blockedUserIds, true);
            });

            $users = User::with(['profile', 'photos'])->whereIn('id', $userIds)->get();

            $allMessages = \App\Models\Message::where(function ($q) use ($user, $userIds) {
                $q->where('sender_id', $user->id)->whereIn('receiver_id', $userIds);
            })->orWhere(function ($q) use ($user, $userIds) {
                $q->whereIn('sender_id', $userIds)->where('receiver_id', $user->id);
            })->latest()->get();

            return $users->map(function ($otherUser) use ($user, $matches, $allMessages) {
                $match = $matches->first(function ($m) use ($user, $otherUser) {
                    return ($m->user1_id === $user->id && $m->user2_id === $otherUser->id) ||
                           ($m->user1_id === $otherUser->id && $m->user2_id === $user->id);
                });

                $lastMessage = $allMessages->first(function ($m) use ($user, $otherUser) {
                    return ($m->sender_id === $user->id && $m->receiver_id === $otherUser->id) ||
                           ($m->sender_id === $otherUser->id && $m->receiver_id === $user->id);
                });

                return [
                    'id' => $match->id,
                    'user1_id' => $match->user1_id,
                    'user2_id' => $match->user2_id,
                    'created_at' => $match->created_at,
                    'updated_at' => $match->updated_at,
                    'last_message' => $lastMessage,
                    'other_user' => $otherUser,
                ];
            });
        }, 60);

        return response()->json(['data' => $conversations]);
    }

    /**
     * Like or Pass a user.
     */
    public function action(MatchActionRequest $request): JsonResponse
    {
        $user = auth()->user();
        $targetUserId = $request->target_user_id;
        $action = $request->action;

        if ($user->id === $targetUserId) {
            return response()->json(['message' => 'Cannot perform action on yourself'], 400);
        }

        if (Block::isBlockedBetween($user->id, (int) $targetUserId)) {
            return response()->json(['message' => 'Cannot interact with a blocked user'], 403);
        }

        // --- EVENT SOURCING ---
        $aggregateId = (string) $user->id;
        $currentVersion = $this->eventStore->getCurrentVersion($aggregateId, 'MatchAction');
        $event = new MatchActionRecorded(
            $aggregateId,
            (int) $targetUserId,
            (string) $action,
            (float) ($user->profile->latitude ?? 0),
            (float) ($user->profile->longitude ?? 0)
        );
        $this->eventStore->append($event, 'MatchAction', $currentVersion + 1);
        // ----------------------

        $this->recordMatchAction($user->id, $targetUserId, $action);

        TaggedCache::flush(["matches_feed:user_{$user->id}"]);

        $isMatch = $this->checkForMatch($user->id, $targetUserId);

        return response()->json([
            'action' => $action,
            'is_match' => $isMatch,
            'message' => $isMatch ? 'It\'s a match!' : 'Action recorded',
        ]);
    }

    private function calculateDistance(UserProfile $profile1, UserProfile $profile2): float
    {
        $lat1 = $profile1->is_travel_mode ? $profile1->travel_latitude : $profile1->latitude;
        $lon1 = $profile1->is_travel_mode ? $profile1->travel_longitude : $profile1->longitude;
        $lat2 = $profile2->is_travel_mode ? $profile2->travel_latitude : $profile2->latitude;
        $lon2 = $profile2->is_travel_mode ? $profile2->travel_longitude : $profile2->longitude;

        if (! $lat1 || ! $lat2) return 0;

        $theta = $lon1 - $lon2;
        $dist = sin(deg2rad($lat1)) * sin(deg2rad($lat2)) +
                cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * cos(deg2rad($theta));
        $dist = acos($dist);
        $dist = rad2deg($dist);
        return $dist * 60 * 1.1515;
    }

    private function recordMatchAction(int $userId, int $targetUserId, string $action): void
    {
        DB::table('match_actions')->updateOrInsert(
            ['user_id' => $userId, 'target_user_id' => $targetUserId],
            ['action' => $action, 'created_at' => now(), 'updated_at' => now()]
        );
    }

    private function checkForMatch(int $userId, int $targetUserId): bool
    {
        $mutualLike = DB::table('match_actions')
            ->where('user_id', $targetUserId)
            ->where('target_user_id', $userId)
            ->where('action', 'like')
            ->exists();

        if ($mutualLike) {
            $user1 = min($userId, $targetUserId);
            $user2 = max($userId, $targetUserId);

            $existing = DB::table('user_matches')
                ->where('user1_id', $user1)
                ->where('user2_id', $user2)
                ->exists();

            if (! $existing) {
                DB::table('user_matches')->insert([
                    'user1_id' => $user1,
                    'user2_id' => $user2,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                TaggedCache::flush(["matches_list:user_{$user1}"]);
                TaggedCache::flush(["matches_list:user_{$user2}"]);

                $userA = User::find($user1);
                $userB = User::find($user2);

                if ($userA && $userB) {
                    $userA->notify(new \App\Notifications\NewMatchNotification($userB));
                    $userB->notify(new \App\Notifications\NewMatchNotification($userA));
                }
            }
        }

        return $mutualLike;
    }

    /**
     * Exchange profiles via NFC tap.
     */
    public function nfcExchange(Request $request): JsonResponse
    {
        $user = auth()->user();
        $peerId = $request->input('peer_id');
        $locationProof = $request->input('location_proof');

        if ($user->id == $peerId) {
            return response()->json(['error' => 'Cannot exchange with yourself'], 422);
        }

        $handshakeKey = "nfc_handshake:" . min($user->id, $peerId) . ":" . max($user->id, $peerId);
        $existingHandshake = \Illuminate\Support\Facades\Redis::get($handshakeKey);

        if (!$existingHandshake) {
            \Illuminate\Support\Facades\Redis::setex($handshakeKey, 15, json_encode([
                'user_id' => $user->id,
                'location' => $locationProof,
                'timestamp' => now()->timestamp
            ]));

            return response()->json([
                'success' => true,
                'message' => 'Handshake initiated. Waiting for peer...',
                'status' => 'pending'
            ]);
        }

        $peerData = json_decode($existingHandshake, true);
        if ($peerData['user_id'] != $peerId) {
             return response()->json(['error' => 'Handshake user mismatch'], 400);
        }

        if ($peerData['location'] !== $locationProof) {
            return response()->json(['error' => 'Location verification failed.'], 403);
        }

        \Illuminate\Support\Facades\Redis::del($handshakeKey);

        $match = \App\Models\UserMatch::updateOrCreate(
            ['user1_id' => min($user->id, $peerId), 'user2_id' => max($user->id, $peerId)],
            ['is_active' => true, 'nfc_verified' => true]
        );

        return response()->json([
            'success' => true,
            'message' => 'NFC and Location verified successfully',
            'status' => 'verified',
            'match_id' => $match->id
        ]);
    }
}
