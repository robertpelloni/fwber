<?php

namespace App\Services;

use App\Models\Group;
use App\Models\GroupMember;
use App\Models\User;
use Illuminate\Support\Collection;

class GroupRankingService
{
    public function __construct(
        private readonly AIMatchingService $aiMatchingService,
        private readonly GroupMatchingService $groupMatchingService,
        private readonly LocalPulseRankingService $localPulseRankingService,
    ) {
    }

    /**
     * @param  Collection<int, Group>  $groups
     * @return Collection<int, Group>
     */
    public function rankMatches(User $viewer, Group $sourceGroup, Collection $groups): Collection
    {
        if ($groups->isEmpty()) {
            return $groups;
        }

        $groups->loadMissing(['activeMembers.user.profile']);

        $trustMap = $this->localPulseRankingService->buildTrustMap(
            $viewer,
            $groups
                ->flatMap(fn (Group $group) => $group->activeMembers->pluck('user_id'))
                ->filter(fn ($id) => is_int($id))
                ->unique()
                ->values()
                ->all()
        );

        return $groups
            ->map(function (Group $candidate) use ($viewer, $sourceGroup, $trustMap) {
                $distanceKm = round((float) ($candidate->distance ?? $this->calculateDistanceKm($sourceGroup, $candidate)), 1);
                $sharedTags = $this->resolveSharedTags($sourceGroup, $candidate);
                $sceneSignals = $this->buildSceneSignals($viewer, $candidate);
                $primaryParticipantId = $this->resolvePrimaryParticipantId($viewer, $candidate, $trustMap);
                $baseCompatibility = $this->groupMatchingService->calculateCompatibilityScore($sourceGroup, $candidate);

                $rankingScore = round(min(100, max(0,
                    ($baseCompatibility * 0.55)
                    + $this->calculateTrustBoost($viewer, $primaryParticipantId, $trustMap)
                    + $this->calculateSceneBoost($sceneSignals)
                    + $this->calculateMemberHealthBoost($candidate)
                    + $this->calculateDistanceBoost($distanceKm)
                )), 2);

                $candidate->distance_km = $distanceKm;
                $candidate->category_match = (bool) ($sourceGroup->category && $candidate->category === $sourceGroup->category);
                $candidate->shared_tags = $sharedTags;
                $candidate->scene_signals = $sceneSignals;
                $candidate->ranking_score = $rankingScore;
                $candidate->match_score = (int) round($rankingScore);
                $candidate->group = $this->buildGroupSummary($candidate);

                return $candidate;
            })
            ->sort(function (Group $left, Group $right) {
                $scoreComparison = ($right->ranking_score <=> $left->ranking_score);
                if ($scoreComparison !== 0) {
                    return $scoreComparison;
                }

                return (($left->distance_km ?? PHP_INT_MAX) <=> ($right->distance_km ?? PHP_INT_MAX));
            })
            ->values();
    }

    public function buildRankingStrategy(): array
    {
        return [
            'compatibility' => true,
            'trusted_members' => true,
            'scene_alignment' => true,
            'member_health' => true,
            'distance' => true,
            'summary' => 'Group matches balance compatibility, trusted members, scene alignment, member health, and distance without exposing private graph details.',
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    private function buildSceneSignals(User $viewer, Group $candidate): ?array
    {
        return $this->aiMatchingService->getSceneSignalsForValues($viewer, array_filter([
            $candidate->name,
            $candidate->description,
            $candidate->category,
            ...($candidate->tags ?? []),
        ]));
    }

    /**
     * @param  array<int, array{is_friend: bool, has_relationship_link: bool, shares_circle: bool}>  $trustMap
     */
    private function resolvePrimaryParticipantId(User $viewer, Group $candidate, array $trustMap): ?int
    {
        $participantIds = $candidate->activeMembers
            ->pluck('user_id')
            ->filter(fn ($id) => is_int($id))
            ->unique()
            ->values();

        if ($participantIds->isEmpty()) {
            return $candidate->created_by_user_id ?: null;
        }

        return $participantIds
            ->sortByDesc(fn (int $participantId) => $this->localPulseRankingService->calculateCompositeScore(
                $viewer,
                $participantId,
                null,
                null,
                $trustMap
            ))
            ->first();
    }

    /**
     * @param  array<int, array{is_friend: bool, has_relationship_link: bool, shares_circle: bool}>  $trustMap
     */
    private function calculateTrustBoost(User $viewer, ?int $primaryParticipantId, array $trustMap): float
    {
        if (! $primaryParticipantId) {
            return 0;
        }

        $trustScore = $this->localPulseRankingService->calculateCompositeScore(
            $viewer,
            $primaryParticipantId,
            null,
            null,
            $trustMap
        );

        return round(min(25, ($trustScore / 80) * 25), 2);
    }

    /**
     * @param  array<string, mixed>|null  $sceneSignals
     */
    private function calculateSceneBoost(?array $sceneSignals): float
    {
        return round(min(15, (($sceneSignals['score_boost'] ?? 0) * 75)), 2);
    }

    private function calculateMemberHealthBoost(Group $candidate): float
    {
        $activeMembers = $candidate->activeMembers instanceof Collection
            ? $candidate->activeMembers
            : collect();
        $activeCount = $activeMembers->count();
        $verifiedCount = $activeMembers->filter(function (GroupMember $member) {
            return (bool) ($member->user?->profile?->is_verified || $member->user?->profile?->is_id_verified);
        })->count();

        return round(
            min(4, $activeCount * 0.75)
            + min(6, $verifiedCount * 2),
            2
        );
    }

    private function calculateDistanceBoost(float $distanceKm): float
    {
        return round(max(0, 10 - min(10, $distanceKm / 5)), 2);
    }

    /**
     * @return array<int, string>
     */
    private function resolveSharedTags(Group $sourceGroup, Group $candidate): array
    {
        return array_values(array_intersect($sourceGroup->tags ?? [], $candidate->tags ?? []));
    }

    /**
     * @return array<string, mixed>
     */
    private function buildGroupSummary(Group $candidate): array
    {
        return [
            'id' => $candidate->id,
            'name' => $candidate->name,
            'description' => $candidate->description,
            'category' => $candidate->category,
            'tags' => $candidate->tags ?? [],
            'member_count' => $candidate->member_count,
            'icon' => $candidate->icon,
            'privacy' => $candidate->privacy,
            'distance_km' => $candidate->distance_km ?? null,
            'scene_signals' => $candidate->scene_signals ?? null,
            'shared_tags' => $candidate->shared_tags ?? [],
        ];
    }

    private function calculateDistanceKm(Group $sourceGroup, Group $candidate): float
    {
        if (! $sourceGroup->location_lat || ! $sourceGroup->location_lon || ! $candidate->location_lat || ! $candidate->location_lon) {
            return 9999;
        }

        $earthRadiusKm = 6371;
        $latFrom = deg2rad($sourceGroup->location_lat);
        $lonFrom = deg2rad($sourceGroup->location_lon);
        $latTo = deg2rad($candidate->location_lat);
        $lonTo = deg2rad($candidate->location_lon);

        $latDelta = $latTo - $latFrom;
        $lonDelta = $lonTo - $lonFrom;

        $angle = 2 * asin(sqrt(
            pow(sin($latDelta / 2), 2)
            + cos($latFrom) * cos($latTo) * pow(sin($lonDelta / 2), 2)
        ));

        return $earthRadiusKm * $angle;
    }
}
