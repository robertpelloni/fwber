<?php

namespace App\Services;

use App\Models\Friend;
use App\Models\RelationshipLink;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;

class LocalPulseRankingService
{
    /**
     * @param  array<int, int>  $authorIds
     * @return array<int, array{is_friend: bool, has_relationship_link: bool, shares_circle: bool}>
     */
    public function buildTrustMap(User $viewer, array $authorIds): array
    {
        $authorIds = array_values(array_unique(array_filter($authorIds, fn ($id) => is_int($id) && $id > 0)));

        if ($authorIds === []) {
            return [];
        }

        $friendIds = [];
        Friend::query()
            ->select('user_id', 'friend_id')
            ->where('status', 'accepted')
            ->where(function ($query) use ($viewer, $authorIds) {
                $query->where(function ($forward) use ($viewer, $authorIds) {
                    $forward->where('user_id', $viewer->id)
                        ->whereIn('friend_id', $authorIds);
                })->orWhere(function ($reverse) use ($viewer, $authorIds) {
                    $reverse->whereIn('user_id', $authorIds)
                        ->where('friend_id', $viewer->id);
                });
            })
            ->get()
            ->each(function (Friend $friend) use ($viewer, &$friendIds) {
                $otherId = $friend->user_id === $viewer->id ? $friend->friend_id : $friend->user_id;
                $friendIds[$otherId] = true;
            });

        $relationshipLinkIds = [];
        RelationshipLink::query()
            ->select('user_id', 'related_user_id')
            ->whereNotNull('confirmed_at')
            ->where(function ($query) use ($viewer, $authorIds) {
                $query->where(function ($forward) use ($viewer, $authorIds) {
                    $forward->where('user_id', $viewer->id)
                        ->whereIn('related_user_id', $authorIds);
                })->orWhere(function ($reverse) use ($viewer, $authorIds) {
                    $reverse->whereIn('user_id', $authorIds)
                        ->where('related_user_id', $viewer->id);
                });
            })
            ->get()
            ->each(function (RelationshipLink $link) use ($viewer, &$relationshipLinkIds) {
                $otherId = $link->user_id === $viewer->id ? $link->related_user_id : $link->user_id;
                $relationshipLinkIds[$otherId] = true;
            });

        $sharedCircleIds = \DB::table('group_members as viewer_memberships')
            ->join('group_members as author_memberships', 'viewer_memberships.group_id', '=', 'author_memberships.group_id')
            ->where('viewer_memberships.user_id', $viewer->id)
            ->whereIn('author_memberships.user_id', $authorIds)
            ->where('viewer_memberships.is_active', true)
            ->where('viewer_memberships.is_banned', false)
            ->where('author_memberships.is_active', true)
            ->where('author_memberships.is_banned', false)
            ->distinct()
            ->pluck('author_memberships.user_id')
            ->flip()
            ->all();

        $trustMap = [];
        foreach ($authorIds as $authorId) {
            $trustMap[$authorId] = [
                'is_friend' => isset($friendIds[$authorId]),
                'has_relationship_link' => isset($relationshipLinkIds[$authorId]),
                'shares_circle' => isset($sharedCircleIds[$authorId]),
            ];
        }

        return $trustMap;
    }

    /**
     * @param  array<string, mixed>|null  $sceneSignals
     * @param  array<int, array{is_friend: bool, has_relationship_link: bool, shares_circle: bool}>  $trustMap
     */
    public function calculateCompositeScore(User $viewer, ?int $authorId, ?CarbonInterface $createdAt, ?array $sceneSignals, array $trustMap): float
    {
        return round(
            $this->calculateTrustScore($viewer, $authorId, $trustMap)
            + $this->calculateSceneScore($sceneSignals)
            + $this->calculateFreshnessScore($createdAt),
            2
        );
    }

    /**
     * @param  array<int, array{is_friend: bool, has_relationship_link: bool, shares_circle: bool}>  $trustMap
     */
    private function calculateTrustScore(User $viewer, ?int $authorId, array $trustMap): float
    {
        if (! $authorId) {
            return 0;
        }

        if ($viewer->id === $authorId) {
            return 80;
        }

        $signals = $trustMap[$authorId] ?? null;
        if (! $signals) {
            return 0;
        }

        if ($signals['has_relationship_link']) {
            return 70;
        }

        if ($signals['is_friend']) {
            return 55;
        }

        if ($signals['shares_circle']) {
            return 30;
        }

        return 0;
    }

    /**
     * @param  array<string, mixed>|null  $sceneSignals
     */
    private function calculateSceneScore(?array $sceneSignals): float
    {
        return (float) (($sceneSignals['score_boost'] ?? 0) * 100);
    }

    private function calculateFreshnessScore(?CarbonInterface $createdAt): float
    {
        if (! $createdAt) {
            return 0;
        }

        $ageMinutes = max(0, Carbon::now()->diffInMinutes($createdAt, false) * -1);

        return max(0, 20 - min(20, $ageMinutes / 30));
    }
}
