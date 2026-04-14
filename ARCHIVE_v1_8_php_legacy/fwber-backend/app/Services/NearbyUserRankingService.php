<?php

namespace App\Services;

use App\Models\MatchAction;
use App\Models\User;
use App\Models\UserLocation;
use App\Models\UserMatch;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

class NearbyUserRankingService
{
    public function __construct(
        private readonly AIMatchingService $aiMatchingService,
        private readonly LocalPulseRankingService $localPulseRankingService,
    ) {
    }

    /**
     * @param  Collection<int, UserLocation>  $locations
     * @return Collection<int, UserLocation>
     */
    public function rankNearby(User $viewer, Collection $locations, float $viewerLatitude, float $viewerLongitude): Collection
    {
        if ($locations->isEmpty()) {
            return $locations;
        }

        $locations->loadMissing(['user.profile', 'user.groups']);

        $candidateIds = $locations
            ->pluck('user_id')
            ->filter(fn ($id) => is_int($id))
            ->unique()
            ->values()
            ->all();

        $trustMap = $this->localPulseRankingService->buildTrustMap($viewer, $candidateIds);
        $positiveActions = MatchAction::query()
            ->where('target_user_id', $viewer->id)
            ->whereIn('user_id', $candidateIds)
            ->whereIn('action', ['like', 'super_like'])
            ->get()
            ->groupBy('user_id');

        $activeMatches = UserMatch::query()
            ->where('is_active', true)
            ->where(function ($query) use ($viewer, $candidateIds) {
                $query->where(function ($forward) use ($viewer, $candidateIds) {
                    $forward->where('user1_id', $viewer->id)
                        ->whereIn('user2_id', $candidateIds);
                })->orWhere(function ($reverse) use ($viewer, $candidateIds) {
                    $reverse->whereIn('user1_id', $candidateIds)
                        ->where('user2_id', $viewer->id);
                });
            })
            ->get()
            ->groupBy(function (UserMatch $match) use ($viewer) {
                return $match->user1_id === $viewer->id
                    ? $match->user2_id
                    : $match->user1_id;
            });

        return $locations
            ->map(function (UserLocation $location) use ($viewer, $viewerLatitude, $viewerLongitude, $trustMap, $positiveActions, $activeMatches) {
                $candidate = $location->user;
                $candidateId = $candidate?->id;
                $sceneSignals = $this->buildSceneSignals($viewer, $location);
                $distanceMeters = (int) round(
                    isset($location->distance)
                        ? (float) $location->distance
                        : $location->distanceTo($viewerLatitude, $viewerLongitude)
                );

                $location->distance = $distanceMeters;
                $location->distance_meters = $distanceMeters;
                $location->scene_signals = $sceneSignals;

                // --- FEDERATED REPUTATION BOOST ---
                $federatedBoost = 0;
                if ($location->user?->profile?->is_federated) {
                    $reputation = \DB::table('federated_actor_reputations')
                        ->where('actor_uri', 'like', '%' . $location->user->name . '%') // Simplified lookup for demo
                        ->first();
                    if ($reputation) {
                        $federatedBoost = min(15, ($reputation->vouch_count / 10)); // Max 15 points boost
                    }
                }
                // ----------------------------------

                $location->ranking_score = round(
                    $this->localPulseRankingService->calculateCompositeScore(
                        $viewer,
                        $candidateId,
                        $this->resolveActivityTimestamp(
                            $location,
                            $positiveActions->get($candidateId, collect()),
                            $activeMatches->get($candidateId, collect())
                        ),
                        $sceneSignals,
                        $trustMap
                    )
                    + $this->calculateActivityBoost(
                        $positiveActions->get($candidateId, collect()),
                        $activeMatches->get($candidateId, collect())
                    )
                    + $this->calculateDistanceScore($distanceMeters)
                    + $federatedBoost,
                    2
                );

                return $location;
            })
            ->sort(function (UserLocation $left, UserLocation $right) {
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
            'trust_connections' => true,
            'scene_alignment' => true,
            'activity_recency' => true,
            'distance' => true,
            'summary' => 'Nearby people balance trusted connections, scene alignment, activity recency, and distance without exposing private graph details.',
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    private function buildSceneSignals(User $viewer, UserLocation $location): ?array
    {
        $candidate = $location->user;
        $profile = $candidate?->profile;

        if (! $candidate || ! $profile) {
            return null;
        }

        return $this->aiMatchingService->getSceneSignalsForValues($viewer, array_filter([
            $profile->display_name,
            $profile->bio,
            $profile->occupation,
            $profile->education,
            $profile->relationship_style,
            $profile->location_name,
            ...($profile->interests ?? []),
            ...($profile->interested_in ?? []),
            ...$candidate->groups->pluck('name')->filter()->all(),
        ]));
    }

    /**
     * @param  Collection<int, MatchAction>  $positiveActions
     * @param  Collection<int, UserMatch>  $activeMatches
     */
    private function resolveActivityTimestamp(UserLocation $location, Collection $positiveActions, Collection $activeMatches): ?CarbonInterface
    {
        $timestamps = collect([
            $location->last_updated,
            $location->user?->last_active_at,
            $positiveActions->sortByDesc('created_at')->first()?->created_at,
            $activeMatches->sortByDesc('created_at')->first()?->created_at,
        ])->filter(fn ($timestamp) => $timestamp instanceof CarbonInterface);

        return $timestamps->sortDesc()->first();
    }

    /**
     * @param  Collection<int, MatchAction>  $positiveActions
     * @param  Collection<int, UserMatch>  $activeMatches
     */
    private function calculateActivityBoost(Collection $positiveActions, Collection $activeMatches): float
    {
        $latestPositiveAction = $positiveActions
            ->sortByDesc('created_at')
            ->first();

        $actionBoost = match ($latestPositiveAction?->action) {
            'super_like' => 12.0,
            'like' => 7.0,
            default => 0.0,
        };

        $matchBoost = $activeMatches->isNotEmpty() ? 8.0 : 0.0;

        return round($actionBoost + $matchBoost, 2);
    }

    private function calculateDistanceScore(int $distanceMeters): float
    {
        return max(0, 20 - min(20, $distanceMeters / 150));
    }
}
