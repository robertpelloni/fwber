<?php

namespace App\Http\Controllers;

use App\Http\Resources\RelationshipLinkResource;
use App\Models\RelationshipLink;
use App\Services\ContentVisibilityService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class RelationshipLinkController extends Controller
{
    public function __construct(
        private readonly ContentVisibilityService $contentVisibilityService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $links = $this->contentVisibilityService->getOwnedRelationshipLinks($user)
            ->map(fn (RelationshipLink $link) => (new RelationshipLinkResource($link))->forOwner($user->id))
            ->values();

        return response()->json([
            'links' => $links,
        ]);
    }

    public function pendingRequests(Request $request): JsonResponse
    {
        $user = $request->user();

        $links = $this->contentVisibilityService->getPendingRelationshipLinks($user)
            ->map(fn (RelationshipLink $link) => (new RelationshipLinkResource($link))->forOwner($user->id))
            ->values();

        return response()->json([
            'requests' => $links,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'related_user_id' => ['required', 'integer', 'exists:users,id', 'different:user_id'],
            'relationship_type' => ['required', Rule::in(RelationshipLink::TYPE_OPTIONS)],
            'visibility' => ['required', Rule::in(RelationshipLink::VISIBILITY_OPTIONS)],
            'note' => ['nullable', 'string', 'max:280'],
        ]);

        $user = $request->user();
        $relatedUserId = (int) $validated['related_user_id'];

        if ($user->id === $relatedUserId) {
            return response()->json([
                'message' => 'You cannot create a relationship link with yourself.',
            ], 422);
        }

        if (! $this->contentVisibilityService->areFriends($user, $relatedUserId)) {
            return response()->json([
                'message' => 'Relationship links can only be proposed to accepted friends.',
            ], 422);
        }

        $existing = RelationshipLink::query()
            ->where(function (Builder $query) use ($user, $relatedUserId) {
                $query->where(function (Builder $forward) use ($user, $relatedUserId) {
                    $forward->where('user_id', $user->id)
                        ->where('related_user_id', $relatedUserId);
                })->orWhere(function (Builder $reverse) use ($user, $relatedUserId) {
                    $reverse->where('user_id', $relatedUserId)
                        ->where('related_user_id', $user->id);
                });
            })
            ->exists();

        if ($existing) {
            return response()->json([
                'message' => 'A relationship link already exists or is awaiting confirmation for this pair.',
            ], 422);
        }

        $link = RelationshipLink::create([
            'user_id' => $user->id,
            'related_user_id' => $relatedUserId,
            'relationship_type' => $validated['relationship_type'],
            'visibility' => $validated['visibility'],
            'note' => $validated['note'] ?? null,
            'requested_at' => now(),
        ])->load(['user.profile', 'relatedUser.profile']);

        return response()->json([
            'link' => (new RelationshipLinkResource($link))->forOwner($user->id),
        ], 201);
    }

    public function respond(Request $request, RelationshipLink $relationshipLink): JsonResponse
    {
        $validated = $request->validate([
            'status' => ['required', Rule::in(['accepted', 'declined'])],
        ]);

        $user = $request->user();

        if ($relationshipLink->related_user_id !== $user->id || $relationshipLink->confirmed_at) {
            return response()->json([
                'message' => 'This relationship link request is not awaiting your response.',
            ], 403);
        }

        if ($validated['status'] === 'accepted') {
            $relationshipLink->confirmed_at = now();
            $relationshipLink->save();
            $relationshipLink->load(['user.profile', 'relatedUser.profile']);

            return response()->json([
                'link' => (new RelationshipLinkResource($relationshipLink))->forOwner($user->id),
            ]);
        }

        $relationshipLink->delete();

        return response()->json([
            'message' => 'Relationship link request declined.',
        ]);
    }

    public function update(Request $request, RelationshipLink $relationshipLink): JsonResponse
    {
        $validated = $request->validate([
            'relationship_type' => ['sometimes', Rule::in(RelationshipLink::TYPE_OPTIONS)],
            'visibility' => ['sometimes', Rule::in(RelationshipLink::VISIBILITY_OPTIONS)],
            'note' => ['sometimes', 'nullable', 'string', 'max:280'],
        ]);

        $user = $request->user();

        if (! $relationshipLink->involvesUser($user->id)) {
            return response()->json([
                'message' => 'Only participants can update this relationship link.',
            ], 403);
        }

        $relationshipLink->fill($validated);
        $relationshipLink->save();
        $relationshipLink->load(['user.profile', 'relatedUser.profile']);

        return response()->json([
            'link' => (new RelationshipLinkResource($relationshipLink))->forOwner($user->id),
        ]);
    }

    public function destroy(Request $request, RelationshipLink $relationshipLink): JsonResponse
    {
        $user = $request->user();

        if (! $relationshipLink->involvesUser($user->id)) {
            return response()->json([
                'message' => 'Only participants can remove this relationship link.',
            ], 403);
        }

        $relationshipLink->delete();

        return response()->json([
            'message' => 'Relationship link removed.',
        ]);
    }
}
