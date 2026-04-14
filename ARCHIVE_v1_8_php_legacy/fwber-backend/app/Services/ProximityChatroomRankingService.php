<?php

namespace App\Services;

use App\Models\ProximityChatroom;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class ProximityChatroomRankingService
{
    public function __construct(
        private readonly AIMatchingService $aiMatchingService,
        private readonly LocalPulseRankingService $localPulseRankingService,
        private readonly LocationService $locationService,
    ) {
    }

    /**
     * @param  Collection<int, ProximityChatroom>  $chatrooms
     * @return Collection<int, ProximityChatroom>
     */
    public function rankNearby(User $viewer, Collection $chatrooms, float $latitude, float $longitude): Collection
    {
        if ($chatrooms->isEmpty()) {
            return $chatrooms;
        }

        $trustMap = $this->localPulseRankingService->buildTrustMap(
            $viewer,
            $chatrooms->pluck('created_by')->filter(fn ($id) => is_int($id))->unique()->values()->all()
        );

        return $chatrooms
            ->map(function (ProximityChatroom $chatroom) use ($viewer, $trustMap, $latitude, $longitude) {
                $distanceMeters = (int) round($chatroom->distance_meters
                    ?? $this->locationService->calculateDistance($latitude, $longitude, $chatroom->latitude, $chatroom->longitude));
                $sceneSignals = $this->buildSceneSignals($viewer, $chatroom);
                $rankingScore = round(
                    $this->localPulseRankingService->calculateCompositeScore(
                        $viewer,
                        $chatroom->created_by,
                        $this->resolveActivityTimestamp($chatroom),
                        $sceneSignals,
                        $trustMap
                    ) + $this->calculateDistanceScore($distanceMeters),
                    2
                );

                $chatroom->distance_meters = $distanceMeters;
                $chatroom->scene_signals = $sceneSignals;
                $chatroom->ranking_score = $rankingScore;

                return $chatroom;
            })
            ->sort(function (ProximityChatroom $left, ProximityChatroom $right) {
                $scoreComparison = ($right->ranking_score <=> $left->ranking_score);
                if ($scoreComparison !== 0) {
                    return $scoreComparison;
                }

                return (($left->distance_meters ?? PHP_INT_MAX) <=> ($right->distance_meters ?? PHP_INT_MAX));
            })
            ->values();
    }

    public function buildRankingStrategy(): array
    {
        return [
            'trusted_connections' => true,
            'scene_alignment' => true,
            'freshness' => true,
            'distance' => true,
            'summary' => 'Nearby chatrooms balance trusted creators, scene alignment, recent activity, and distance without exposing private graph details.',
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    private function buildSceneSignals(User $viewer, ProximityChatroom $chatroom): ?array
    {
        return $this->aiMatchingService->getSceneSignalsForValues($viewer, array_filter([
            $chatroom->name,
            $chatroom->description,
            $chatroom->type,
            $chatroom->venue_name,
            $chatroom->venue_type,
            $chatroom->event_name,
            ...($chatroom->tags ?? []),
        ]));
    }

    private function resolveActivityTimestamp(ProximityChatroom $chatroom): ?CarbonInterface
    {
        $timestamp = $chatroom->last_activity_at ?? $chatroom->created_at ?? null;
        if ($timestamp instanceof CarbonInterface) {
            return $timestamp;
        }

        if (! $timestamp) {
            return null;
        }

        try {
            return Carbon::parse($timestamp);
        } catch (\Throwable) {
            return null;
        }
    }

    private function calculateDistanceScore(int $distanceMeters): float
    {
        return max(0, 20 - min(20, $distanceMeters / 100));
    }
}
