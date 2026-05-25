<?php

namespace App\Services;

use App\Models\Promotion;
use App\Models\User;
use Illuminate\Support\Collection;

class DealRankingService
{
    public function __construct(
        private readonly AIMatchingService $aiMatchingService,
        private readonly LocalPulseRankingService $localPulseRankingService,
        private readonly LocationService $locationService,
    ) {
    }

    /**
     * @param  Collection<int, Promotion>  $deals
     * @return Collection<int, Promotion>
     */
    public function rankNearby(User $viewer, Collection $deals, float $latitude, float $longitude): Collection
    {
        if ($deals->isEmpty()) {
            return $deals;
        }

        $deals->loadMissing(['merchant.user.profile']);

        $merchantUserIds = $deals
            ->map(fn (Promotion $promotion) => $promotion->merchant?->user_id)
            ->filter(fn ($id) => is_int($id))
            ->unique()
            ->values()
            ->all();

        $trustMap = $this->localPulseRankingService->buildTrustMap($viewer, $merchantUserIds);

        return $deals
            ->map(function (Promotion $deal) use ($viewer, $latitude, $longitude, $trustMap) {
                $merchant = $deal->merchant;
                $merchantUserId = $merchant?->user_id;
                $sceneSignals = $this->buildSceneSignals($viewer, $deal);
                $distanceMeters = $this->locationService->calculateDistance(
                    $latitude,
                    $longitude,
                    (float) $deal->lat,
                    (float) $deal->lng
                );

                $deal->distance_meters = $distanceMeters;
                $deal->scene_signals = $sceneSignals;
                $deal->ranking_score = round(
                    $this->localPulseRankingService->calculateCompositeScore(
                        $viewer,
                        $merchantUserId,
                        $deal->created_at,
                        $sceneSignals,
                        $trustMap
                    ) + $this->calculateDealHealthScore($deal) + $this->calculateDistanceScore($distanceMeters),
                    2
                );

                return $deal;
            })
            ->sort(function (Promotion $left, Promotion $right) {
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
            'trusted_merchants' => true,
            'scene_alignment' => true,
            'deal_health' => true,
            'freshness' => true,
            'distance' => true,
            'summary' => 'Nearby deals balance trusted merchants, scene alignment, deal health, freshness, and distance without exposing private graph details.',
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    private function buildSceneSignals(User $viewer, Promotion $deal): ?array
    {
        return $this->aiMatchingService->getSceneSignalsForValues($viewer, array_filter([
            $deal->title,
            $deal->description,
            $deal->merchant?->business_name,
            $deal->merchant?->category,
            $deal->merchant?->description,
            $deal->merchant?->address,
        ]));
    }

    private function calculateDealHealthScore(Promotion $deal): float
    {
        $verificationBoost = match ($deal->merchant?->verification_status) {
            'verified' => 8.0,
            'pending' => 3.0,
            default => 0.0,
        };
        $discountBoost = min(8, ((float) $deal->discount_value) / 5);
        $engagementBoost = min(10, ($deal->views * 0.1) + ($deal->clicks * 0.5) + ($deal->redemptions * 2));
        $expiryBoost = $deal->expires_at
            ? max(0, 8 - min(8, now()->diffInHours($deal->expires_at, false) <= 0 ? 999 : now()->diffInHours($deal->expires_at) / 6))
            : 0.0;

        return round($verificationBoost + $discountBoost + $engagementBoost + $expiryBoost, 2);
    }

    private function calculateDistanceScore(int $distanceMeters): float
    {
        return max(0, 20 - min(20, $distanceMeters / 150));
    }
}
