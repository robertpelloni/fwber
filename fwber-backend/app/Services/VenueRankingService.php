<?php

namespace App\Services;

use App\Models\User;
use App\Models\Venue;
use App\Models\VenueCheckin;
use Illuminate\Support\Collection;

class VenueRankingService
{
    public function __construct(
        private readonly AIMatchingService $aiMatchingService,
        private readonly LocalPulseRankingService $localPulseRankingService,
    ) {
    }

    /**
     * @param  Collection<int, Venue>  $venues
     * @return Collection<int, Venue>
     */
    public function rankNearby(User $viewer, Collection $venues): Collection
    {
        if ($venues->isEmpty()) {
            return $venues;
        }

        $venues->loadMissing(['recentCheckins.user.profile']);

        $trustMap = $this->localPulseRankingService->buildTrustMap(
            $viewer,
            $venues
                ->flatMap(fn (Venue $venue) => $venue->recentCheckins->pluck('user_id'))
                ->filter(fn ($id) => is_int($id))
                ->unique()
                ->values()
                ->all()
        );

        return $venues
            ->map(function (Venue $venue) use ($viewer, $trustMap) {
                $sceneSignals = $this->buildSceneSignals($viewer, $venue);
                $primaryParticipantId = $this->resolvePrimaryParticipantId($viewer, $venue->recentCheckins, $trustMap);
                $distanceMeters = (int) round((float) ($venue->distance ?? 0));
                $rankingScore = round(
                    $this->localPulseRankingService->calculateCompositeScore(
                        $viewer,
                        $primaryParticipantId,
                        $this->resolveActivityTimestamp($venue),
                        $sceneSignals,
                        $trustMap
                    ) + $this->calculateVenueHealthScore($venue) + $this->calculateDistanceScore($distanceMeters),
                    2
                );

                $venue->distance_meters = $distanceMeters;
                $venue->scene_signals = $sceneSignals;
                $venue->ranking_score = $rankingScore;

                return $venue;
            })
            ->sort(function (Venue $left, Venue $right) {
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
            'trusted_visitors' => true,
            'scene_alignment' => true,
            'venue_health' => true,
            'freshness' => true,
            'distance' => true,
            'summary' => 'Nearby venues balance trusted recent visitors, scene alignment, venue health, freshness, and distance without exposing private graph details.',
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    private function buildSceneSignals(User $viewer, Venue $venue): ?array
    {
        return $this->aiMatchingService->getSceneSignalsForValues($viewer, array_filter([
            $venue->name,
            $venue->description,
            $venue->business_type,
            $venue->city,
            $venue->state,
            ...($venue->features ?? []),
            ...$venue->recentCheckins->take(5)->pluck('message')->filter()->all(),
        ]));
    }

    /**
     * @param  Collection<int, VenueCheckin>  $recentCheckins
     * @param  array<int, array{is_friend: bool, has_relationship_link: bool, shares_circle: bool}>  $trustMap
     */
    private function resolvePrimaryParticipantId(User $viewer, Collection $recentCheckins, array $trustMap): ?int
    {
        $participantIds = $recentCheckins
            ->pluck('user_id')
            ->filter(fn ($id) => is_int($id))
            ->unique()
            ->values();

        if ($participantIds->isEmpty()) {
            return null;
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

    private function resolveActivityTimestamp(Venue $venue)
    {
        return $venue->recentCheckins
            ->pluck('created_at')
            ->filter()
            ->sortDesc()
            ->first();
    }

    private function calculateVenueHealthScore(Venue $venue): float
    {
        $verificationBoost = match ($venue->verification_status) {
            'verified' => 8.0,
            'pending' => 3.0,
            default => 0.0,
        };
        $checkinBoost = min(10, (float) (($venue->active_checkins ?? 0) * 2.5));
        $capacityBoost = min(6, ((float) ($venue->max_capacity ?? 0)) / 100);
        $featureBoost = min(4, count($venue->features ?? []));

        return round($verificationBoost + $checkinBoost + $capacityBoost + $featureBoost, 2);
    }

    private function calculateDistanceScore(int $distanceMeters): float
    {
        return max(0, 20 - min(20, $distanceMeters / 150));
    }
}
