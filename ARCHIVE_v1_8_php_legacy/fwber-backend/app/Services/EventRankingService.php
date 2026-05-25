<?php

namespace App\Services;

use App\Models\Event;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class EventRankingService
{
    public function __construct(
        private readonly AIMatchingService $aiMatchingService,
        private readonly LocalPulseRankingService $localPulseRankingService,
        private readonly LocationService $locationService,
    ) {
    }

    /**
     * @param  Collection<int, Event>  $events
     * @return Collection<int, Event>
     */
    public function rankNearby(User $viewer, Collection $events, float $latitude, float $longitude): Collection
    {
        if ($events->isEmpty()) {
            return $events;
        }

        $trustMap = $this->localPulseRankingService->buildTrustMap(
            $viewer,
            $events->pluck('created_by_user_id')->filter(fn ($id) => is_int($id))->unique()->values()->all()
        );

        return $events
            ->map(function (Event $event) use ($viewer, $trustMap, $latitude, $longitude) {
                $distanceMeters = (int) round($event->distance_meters
                    ?? $this->locationService->calculateDistance($latitude, $longitude, $event->latitude, $event->longitude));
                $sceneSignals = $this->buildSceneSignals($viewer, $event);
                $rankingScore = round(
                    $this->localPulseRankingService->calculateCompositeScore(
                        $viewer,
                        $event->created_by_user_id,
                        $this->resolveActivityTimestamp($event),
                        $sceneSignals,
                        $trustMap
                    ) + $this->calculateDistanceScore($distanceMeters),
                    2
                );

                $event->distance_meters = $distanceMeters;
                $event->scene_signals = $sceneSignals;
                $event->ranking_score = $rankingScore;

                return $event;
            })
            ->sort(function (Event $left, Event $right) {
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
            'summary' => 'Nearby events balance trusted organizers, scene alignment, freshness, and distance without exposing private graph details.',
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    private function buildSceneSignals(User $viewer, Event $event): ?array
    {
        return $this->aiMatchingService->getSceneSignalsForValues($viewer, array_filter([
            $event->title,
            $event->description,
            $event->type,
            $event->location_name,
        ]));
    }

    private function resolveActivityTimestamp(Event $event): ?CarbonInterface
    {
        $timestamp = $event->starts_at ?? $event->created_at ?? null;
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
