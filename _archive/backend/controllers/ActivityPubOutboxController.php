<?php

namespace App\Http\Controllers;

use App\Models\ProximityArtifact;
use App\Models\User;
use Illuminate\Http\Request;

class ActivityPubOutboxController extends Controller
{
    /**
     * Lists activities published by this user.
     *
     * @param  int  $id  The local user ID
     */
    public function index(Request $request, $id)
    {
        $user = User::where('id', $id)
            ->whereHas('profile', function ($q) {
                $q->where('is_federated', true);
            })->first();

        if (! $user) {
            return response()->json(['error' => 'Outbox not found'], 404);
        }

        $limit = min(max((int) $request->query('limit', 20), 1), 50);
        $outboxId = url("/api/federation/users/{$user->id}/outbox");
        $pageId = "{$outboxId}?page=true&limit={$limit}";

        $baseQuery = ProximityArtifact::query()
            ->where('user_id', $user->id)
            ->type('board_post')
            ->active()
            ->orderByDesc('created_at');

        $totalItems = (clone $baseQuery)->count();
        $artifacts = (clone $baseQuery)
            ->limit($limit)
            ->get();

        if ($request->boolean('page')) {
            return response()->json([
                '@context' => 'https://www.w3.org/ns/activitystreams',
                'id' => $pageId,
                'type' => 'OrderedCollectionPage',
                'partOf' => $outboxId,
                'totalItems' => $totalItems,
                'orderedItems' => $artifacts
                    ->map(fn (ProximityArtifact $artifact) => $this->mapArtifactToCreateActivity($user, $artifact))
                    ->values()
                    ->all(),
            ])->header('Content-Type', 'application/activity+json');
        }

        return response()->json([
            '@context' => 'https://www.w3.org/ns/activitystreams',
            'id' => $outboxId,
            'type' => 'OrderedCollection',
            'totalItems' => $totalItems,
            'first' => $pageId,
        ])->header('Content-Type', 'application/activity+json');
    }

    private function mapArtifactToCreateActivity(User $user, ProximityArtifact $artifact): array
    {
        $actorUri = url("/api/federation/users/{$user->id}");
        $activityId = "{$actorUri}/activities/board-post-{$artifact->id}";
        $noteId = "{$actorUri}/notes/board-post-{$artifact->id}";
        $published = $artifact->created_at?->toIso8601String() ?? now()->toIso8601String();

        return [
            'id' => $activityId,
            'type' => 'Create',
            'actor' => $actorUri,
            'published' => $published,
            'to' => ['https://www.w3.org/ns/activitystreams#Public'],
            'object' => [
                'id' => $noteId,
                'type' => 'Note',
                'attributedTo' => $actorUri,
                'content' => $artifact->content,
                'published' => $published,
                'to' => ['https://www.w3.org/ns/activitystreams#Public'],
            ],
        ];
    }
}
