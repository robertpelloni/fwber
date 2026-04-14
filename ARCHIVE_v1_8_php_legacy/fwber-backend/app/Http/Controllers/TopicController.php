<?php

namespace App\Http\Controllers;

use App\Http\Resources\JournalResource;
use App\Http\Resources\TopicResource;
use App\Models\Group;
use App\Models\Journal;
use App\Models\ProximityArtifact;
use App\Models\Topic;
use App\Models\User;
use App\Services\ContentVisibilityService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class TopicController extends Controller
{
    public function __construct(
        private readonly ContentVisibilityService $contentVisibilityService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'search' => ['nullable', 'string', 'max:80'],
            'category' => ['nullable', 'string', 'max:50'],
            'featured' => ['nullable', 'boolean'],
            'followed' => ['nullable', 'boolean'],
        ]);

        /** @var User $user */
        $user = $request->user();

        $query = Topic::query()
            ->orderByDesc('is_featured')
            ->orderBy('sort_order')
            ->orderBy('label');

        if (! empty($validated['category'])) {
            $query->where('category', $validated['category']);
        }

        if (array_key_exists('featured', $validated)) {
            $query->where('is_featured', (bool) $validated['featured']);
        }

        if (! empty($validated['search'])) {
            $search = mb_strtolower(trim($validated['search']));

            $query->where(function ($builder) use ($search) {
                $builder->whereRaw('LOWER(slug) like ?', ["%{$search}%"])
                    ->orWhereRaw('LOWER(label) like ?', ["%{$search}%"])
                    ->orWhereRaw('LOWER(description) like ?', ["%{$search}%"]);
            });
        }

        if (! empty($validated['followed'])) {
            $query->whereHas('followers', fn ($builder) => $builder->where('users.id', $user->id));
        }

        $topics = $query->get()->map(fn (Topic $topic) => $this->decorateTopic($topic, $user));

        return response()->json([
            'topics' => TopicResource::collection($topics),
        ]);
    }

    public function followed(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $topics = $user->followedTopics()
            ->orderByDesc('is_featured')
            ->orderBy('sort_order')
            ->orderBy('label')
            ->get()
            ->map(fn (Topic $topic) => $this->decorateTopic($topic, $user));

        return response()->json([
            'topics' => TopicResource::collection($topics),
        ]);
    }

    public function show(Request $request, string $slug): JsonResponse
    {
        /** @var User $viewer */
        $viewer = $request->user();
        $topic = Topic::query()->where('slug', $slug)->firstOrFail();

        $groups = $this->getTopicGroups($topic);
        $journals = $this->getTopicJournals($topic, $viewer);
        $artifacts = $this->getTopicArtifacts($topic);

        return response()->json([
            'topic' => new TopicResource($this->decorateTopic($topic, $viewer, $groups, $journals, $artifacts)),
            'groups' => $groups->map(fn (Group $group) => [
                'id' => $group->id,
                'name' => $group->name,
                'description' => $group->description,
                'icon' => $group->icon,
                'privacy' => $group->privacy,
                'category' => $group->category,
                'tags' => $group->tags ?? [],
                'member_count' => (int) $group->member_count,
                'matching_enabled' => (bool) $group->matching_enabled,
            ])->values(),
            'journals' => JournalResource::collection($journals),
            'artifacts' => $artifacts->map(fn (ProximityArtifact $artifact) => [
                'id' => $artifact->id,
                'type' => $artifact->type,
                'content' => $artifact->content,
                'lat' => $artifact->fuzzed_latitude,
                'lng' => $artifact->fuzzed_longitude,
                'radius' => $artifact->visibility_radius_m,
                'expires_at' => $artifact->expires_at?->toIso8601String(),
                'user_id' => $artifact->user_id,
                'comments_count' => $artifact->comments_count ?? 0,
                'votes_sum_value' => (int) ($artifact->votes_sum_value ?? 0),
                'meta' => $artifact->meta,
                'created_at' => $artifact->created_at?->toIso8601String(),
            ])->values(),
        ]);
    }

    public function follow(Request $request, string $slug): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $topic = Topic::query()->where('slug', $slug)->firstOrFail();

        $user->followedTopics()->syncWithoutDetaching([
            $topic->id => ['followed_at' => now()],
        ]);

        return response()->json([
            'topic' => new TopicResource($this->decorateTopic($topic->fresh(), $user)),
            'message' => 'Topic followed.',
        ]);
    }

    public function unfollow(Request $request, string $slug): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $topic = Topic::query()->where('slug', $slug)->firstOrFail();

        $user->followedTopics()->detach($topic->id);

        return response()->json([
            'topic' => new TopicResource($this->decorateTopic($topic->fresh(), $user)),
            'message' => 'Topic unfollowed.',
        ]);
    }

    private function decorateTopic(
        Topic $topic,
        User $viewer,
        ?Collection $groups = null,
        ?Collection $journals = null,
        ?Collection $artifacts = null
    ): Topic {
        $groups ??= $this->getTopicGroups($topic);
        $journals ??= $this->getTopicJournals($topic, $viewer, 50);
        $artifacts ??= $this->getTopicArtifacts($topic, 50);

        $topic->setAttribute('follower_count', $topic->followers()->count());
        $topic->setAttribute('group_count', $groups->count());
        $topic->setAttribute('journal_count', $journals->count());
        $topic->setAttribute('artifact_count', $artifacts->count());
        $topic->setAttribute('is_followed', $viewer->followedTopics()->where('topics.id', $topic->id)->exists());

        return $topic;
    }

    private function getTopicGroups(Topic $topic, int $limit = 12): Collection
    {
        return Group::public()
            ->active()
            ->orderByDesc('created_at')
            ->get()
            ->filter(function (Group $group) use ($topic) {
                return $topic->matchesTokens([
                    $group->category,
                    ...($group->tags ?? []),
                ]);
            })
            ->take($limit)
            ->values();
    }

    private function getTopicJournals(Topic $topic, User $viewer, int $limit = 8): Collection
    {
        return Journal::query()
            ->with(['circleGroup:id,name,privacy', 'user'])
            ->latest()
            ->get()
            ->filter(function (Journal $journal) use ($topic, $viewer) {
                return $topic->matchesTokens($journal->tags ?? [])
                    && $this->contentVisibilityService->canViewJournal($viewer, $journal);
            })
            ->take($limit)
            ->values();
    }

    private function getTopicArtifacts(Topic $topic, int $limit = 10): Collection
    {
        return ProximityArtifact::query()
            ->active()
            ->withCount('comments')
            ->withSum('votes', 'value')
            ->latest()
            ->get()
            ->filter(function (ProximityArtifact $artifact) use ($topic) {
                return data_get($artifact->meta, 'topic_slug') === $topic->slug;
            })
            ->take($limit)
            ->values();
    }
}
