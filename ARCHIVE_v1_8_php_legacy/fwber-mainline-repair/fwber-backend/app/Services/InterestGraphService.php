<?php

namespace App\Services;

use App\Models\Topic;
use App\Models\User;
use Illuminate\Support\Collection;

class InterestGraphService
{
    /**
     * @param  array<int, mixed>  $values
     * @return array<int, string>
     */
    public function canonicalizeInterestValues(array $values): array
    {
        $topics = $this->getAllTopics();
        $canonical = [];

        foreach (Topic::normalizeTerms($values) as $value) {
            $matchedTopics = $topics->filter(fn (Topic $topic) => $topic->matchesTokens([$value]))->values();

            if ($matchedTopics->isEmpty()) {
                $canonical[] = $value;

                continue;
            }

            foreach ($matchedTopics as $topic) {
                $canonical[] = $topic->slug;
            }
        }

        return array_values(array_unique($canonical));
    }

    /**
     * @param  array<int, mixed>|null  $profileInterests
     * @return Collection<int, Topic>
     */
    public function syncProfileInterests(User $user, ?array $profileInterests): Collection
    {
        $canonicalInterests = $this->canonicalizeInterestValues($profileInterests ?? []);
        $matchedTopics = $this->resolveTopics($canonicalInterests);

        if ($matchedTopics->isNotEmpty()) {
            $user->followedTopics()->syncWithoutDetaching(
                $matchedTopics->mapWithKeys(fn (Topic $topic) => [
                    $topic->id => ['followed_at' => now()],
                ])->all()
            );
        }

        return $this->buildInterestTopics($user, $canonicalInterests);
    }

    /**
     * @param  array<int, mixed>|null  $profileInterests
     * @return Collection<int, Topic>
     */
    public function buildInterestTopics(User $user, ?array $profileInterests = null, int $limit = 12): Collection
    {
        $canonicalInterests = $this->canonicalizeInterestValues($profileInterests ?? ($user->profile?->interests ?? []));
        $matchedTopics = $this->resolveTopics($canonicalInterests)->keyBy('id');
        $followedTopics = ($user->relationLoaded('followedTopics')
            ? $user->followedTopics
            : $user->followedTopics()->orderByDesc('is_featured')->orderBy('sort_order')->orderBy('label')->get())
            ->keyBy('id');

        $mergedTopics = $followedTopics
            ->union($matchedTopics)
            ->map(function (Topic $topic) use ($followedTopics, $matchedTopics) {
                $clone = clone $topic;
                $isFollowed = $followedTopics->has($topic->id);
                $isMatched = $matchedTopics->has($topic->id);

                $clone->setAttribute(
                    'match_source',
                    $isFollowed && $isMatched ? 'both' : ($isFollowed ? 'followed' : 'profile')
                );
                $clone->setAttribute('is_followed', $isFollowed);

                return $clone;
            })
            ->sortBy([
                fn (Topic $topic) => match ($topic->match_source) {
                    'both' => 0,
                    'followed' => 1,
                    default => 2,
                },
                fn (Topic $topic) => $topic->is_featured ? 0 : 1,
                fn (Topic $topic) => $topic->sort_order ?? PHP_INT_MAX,
                fn (Topic $topic) => mb_strtolower($topic->label),
            ])
            ->take($limit)
            ->values();

        return $mergedTopics;
    }

    /**
     * @param  array<int, string>  $canonicalInterests
     * @return Collection<int, Topic>
     */
    private function resolveTopics(array $canonicalInterests): Collection
    {
        if ($canonicalInterests === []) {
            return collect();
        }

        $topics = $this->getAllTopics();

        return $topics
            ->filter(fn (Topic $topic) => $topic->matchesTokens($canonicalInterests))
            ->values();
    }

    /**
     * @return Collection<int, Topic>
     */
    private function getAllTopics(): Collection
    {
        return Topic::query()
            ->orderByDesc('is_featured')
            ->orderBy('sort_order')
            ->orderBy('label')
            ->get();
    }
}
