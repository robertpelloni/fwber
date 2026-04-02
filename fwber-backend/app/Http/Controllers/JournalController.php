<?php

namespace App\Http\Controllers;

use App\Http\Resources\JournalResource;
use App\Models\Journal;
use App\Models\User;
use App\Services\ContentVisibilityService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class JournalController extends Controller
{
    public function __construct(
        private readonly ContentVisibilityService $visibilityService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'visibility' => ['nullable', Rule::in(Journal::VISIBILITY_OPTIONS)],
        ]);

        $user = $request->user();

        $query = $user->journals()->with('circleGroup:id,name,privacy')->latest();

        if ($request->filled('visibility')) {
            $query->where('visibility', $request->string('visibility')->toString());
        }

        $journals = $query->get();

        return response()->json([
            'journals' => JournalResource::collection($journals),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'title' => ['nullable', 'string', 'max:120'],
            'content' => ['required', 'string', 'max:5000'],
            'visibility' => ['nullable', Rule::in(Journal::VISIBILITY_OPTIONS)],
            'circle_group_id' => ['nullable', 'integer', 'exists:groups,id'],
            'tags' => ['nullable', 'array', 'max:8'],
            'tags.*' => ['string', 'max:40'],
            'mood_emoji' => ['nullable', 'string', 'max:10'],
            'accent_color' => ['nullable', 'string', 'max:20'],
        ]);

        $user = $request->user();
        $profile = $user->profile()->firstOrCreate(['user_id' => $user->id]);
        $visibility = $validated['visibility'] ?? $profile->journal_visibility_default ?? Journal::VISIBILITY_FRIENDS;
        $circleGroupId = $validated['circle_group_id'] ?? $profile->journal_circle_group_id;

        if ($visibility === Journal::VISIBILITY_CIRCLE) {
            $group = $this->visibilityService->ensureUserCanUseCircle($user, $circleGroupId);

            if (! $group) {
                return response()->json([
                    'message' => 'Select one of your active groups to use circle visibility.',
                ], 422);
            }
        } else {
            $circleGroupId = null;
        }

        $journal = Journal::create([
            'user_id' => $user->id,
            'title' => $validated['title'] ?? null,
            'content' => $validated['content'],
            'visibility' => $visibility,
            'circle_group_id' => $circleGroupId,
            'tags' => $validated['tags'] ?? [],
            'mood_emoji' => $validated['mood_emoji'] ?? null,
            'accent_color' => $validated['accent_color'] ?? null,
        ])->load('circleGroup:id,name,privacy');

        return response()->json([
            'journal' => new JournalResource($journal),
        ], 201);
    }

    public function show(Request $request, Journal $journal): JsonResponse
    {
        $journal->load('circleGroup:id,name,privacy', 'user');

        if (! $this->visibilityService->canViewJournal($request->user(), $journal)) {
            return response()->json(['message' => 'You do not have access to this field note.'], 403);
        }

        return response()->json([
            'journal' => new JournalResource($journal),
        ]);
    }

    public function update(Request $request, Journal $journal): JsonResponse
    {
        if ($request->user()->id !== $journal->user_id) {
            return response()->json(['message' => 'Only the author can update this field note.'], 403);
        }

        $validated = $request->validate([
            'title' => ['sometimes', 'nullable', 'string', 'max:120'],
            'content' => ['sometimes', 'required', 'string', 'max:5000'],
            'visibility' => ['sometimes', Rule::in(Journal::VISIBILITY_OPTIONS)],
            'circle_group_id' => ['sometimes', 'nullable', 'integer', 'exists:groups,id'],
            'tags' => ['sometimes', 'nullable', 'array', 'max:8'],
            'tags.*' => ['string', 'max:40'],
            'mood_emoji' => ['sometimes', 'nullable', 'string', 'max:10'],
            'accent_color' => ['sometimes', 'nullable', 'string', 'max:20'],
        ]);

        $visibility = $validated['visibility'] ?? $journal->visibility;
        $circleGroupId = array_key_exists('circle_group_id', $validated)
            ? $validated['circle_group_id']
            : $journal->circle_group_id;

        if ($visibility === Journal::VISIBILITY_CIRCLE) {
            $group = $this->visibilityService->ensureUserCanUseCircle($request->user(), $circleGroupId);

            if (! $group) {
                return response()->json([
                    'message' => 'Select one of your active groups to use circle visibility.',
                ], 422);
            }
        } else {
            $circleGroupId = null;
        }

        $journal->fill([
            'title' => $validated['title'] ?? $journal->title,
            'content' => $validated['content'] ?? $journal->content,
            'visibility' => $visibility,
            'circle_group_id' => $circleGroupId,
            'tags' => $validated['tags'] ?? $journal->tags,
            'mood_emoji' => array_key_exists('mood_emoji', $validated) ? $validated['mood_emoji'] : $journal->mood_emoji,
            'accent_color' => array_key_exists('accent_color', $validated) ? $validated['accent_color'] : $journal->accent_color,
        ]);
        $journal->save();
        $journal->load('circleGroup:id,name,privacy');

        return response()->json([
            'journal' => new JournalResource($journal),
        ]);
    }

    public function destroy(Request $request, Journal $journal): JsonResponse
    {
        if ($request->user()->id !== $journal->user_id) {
            return response()->json(['message' => 'Only the author can delete this field note.'], 403);
        }

        $journal->delete();

        return response()->json(['message' => 'Field note deleted.']);
    }
}
