<?php

namespace App\Services;

use App\Models\AudioRoom;
use App\Models\User;
use App\Models\UserLocation;
use Illuminate\Support\Collection;

class AudioRoomRankingService
{
    public function __construct(
        private readonly AIMatchingService $aiMatchingService,
        private readonly LocalPulseRankingService $localPulseRankingService,
        private readonly LocationService $locationService,
    ) {
    }

    /**
     * @param  Collection<int, AudioRoom>  $rooms
     * @return Collection<int, AudioRoom>
     */
    public function rankLobby(User $viewer, Collection $rooms, ?float $latitude = null, ?float $longitude = null): Collection
    {
        if ($rooms->isEmpty()) {
            return $rooms;
        }

        $rooms->loadMissing(['host.profile', 'participants']);

        $hostIds = $rooms->pluck('host_id')
            ->filter(fn ($id) => is_int($id))
            ->unique()
            ->values()
            ->all();

        $trustMap = $this->localPulseRankingService->buildTrustMap($viewer, $hostIds);
        $hostLocations = UserLocation::query()
            ->active()
            ->whereIn('user_id', $hostIds)
            ->get()
            ->keyBy('user_id');

        return $rooms
            ->map(function (AudioRoom $room) use ($viewer, $latitude, $longitude, $trustMap, $hostLocations) {
                $sceneSignals = $this->buildSceneSignals($viewer, $room);
                $distanceMeters = $this->resolveDistanceMeters(
                    $room,
                    $hostLocations,
                    $latitude,
                    $longitude
                );

                $room->distance_meters = $distanceMeters;
                $room->scene_signals = $sceneSignals;
                $room->ranking_score = round(
                    $this->localPulseRankingService->calculateCompositeScore(
                        $viewer,
                        $room->host_id,
                        $room->updated_at ?? $room->created_at,
                        $sceneSignals,
                        $trustMap
                    ) + $this->calculateParticipantHealthScore($room) + $this->calculateDistanceScore($distanceMeters),
                    2
                );

                return $room;
            })
            ->sort(function (AudioRoom $left, AudioRoom $right) {
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
            'trusted_hosts' => true,
            'scene_alignment' => true,
            'participant_health' => true,
            'freshness' => true,
            'distance' => true,
            'summary' => 'Audio rooms balance trusted hosts, scene alignment, active participation, freshness, and distance without exposing private graph details.',
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    private function buildSceneSignals(User $viewer, AudioRoom $room): ?array
    {
        return $this->aiMatchingService->getSceneSignalsForValues($viewer, array_filter([
            $room->name,
            $room->topic,
            $room->host?->profile?->bio,
            ...($room->host?->profile?->interests ?? []),
        ]));
    }

    /**
     * @param  Collection<int, UserLocation>  $hostLocations
     */
    private function resolveDistanceMeters(AudioRoom $room, Collection $hostLocations, ?float $latitude, ?float $longitude): ?int
    {
        if ($latitude === null || $longitude === null) {
            return null;
        }

        $hostLocation = $hostLocations->get($room->host_id);
        if (! $hostLocation) {
            return null;
        }

        return $this->locationService->calculateDistance(
            $latitude,
            $longitude,
            (float) $hostLocation->latitude,
            (float) $hostLocation->longitude
        );
    }

    private function calculateParticipantHealthScore(AudioRoom $room): float
    {
        $participantCount = (int) ($room->participants_count ?? $room->participants->count());

        return min(12, $participantCount * 3);
    }

    private function calculateDistanceScore(?int $distanceMeters): float
    {
        if ($distanceMeters === null) {
            return 0;
        }

        return max(0, 20 - min(20, $distanceMeters / 150));
    }
}
