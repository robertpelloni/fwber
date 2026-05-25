<?php

namespace App\Services;

use App\Models\BulletinBoard;
use App\Models\BulletinMessage;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class BulletinBoardRankingService
{
    public function __construct(
        private readonly AIMatchingService $aiMatchingService,
        private readonly LocalPulseRankingService $localPulseRankingService,
    ) {
    }

    /**
     * @param  Collection<int, BulletinBoard>  $boards
     * @return Collection<int, BulletinBoard>
     */
    public function rankNearby(User $viewer, Collection $boards, float $latitude, float $longitude): Collection
    {
        if ($boards->isEmpty()) {
            return $boards;
        }

        $trustMap = $this->localPulseRankingService->buildTrustMap(
            $viewer,
            $boards
                ->flatMap(fn (BulletinBoard $board) => $board->recentMessages->pluck('user_id'))
                ->filter(fn ($id) => is_int($id))
                ->unique()
                ->values()
                ->all()
        );

        return $boards
            ->map(function (BulletinBoard $board) use ($viewer, $trustMap, $latitude, $longitude) {
                $distanceMeters = (int) round($board->distance_meters ?? $board->distanceFrom($latitude, $longitude));
                $sceneSignals = $this->buildSceneSignals($viewer, $board);
                $primaryParticipantId = $this->resolvePrimaryParticipantId($viewer, $board->recentMessages, $trustMap);
                $rankingScore = round(
                    $this->localPulseRankingService->calculateCompositeScore(
                        $viewer,
                        $primaryParticipantId,
                        $this->resolveActivityTimestamp($board),
                        $sceneSignals,
                        $trustMap
                    ) + $this->calculateHealthScore($board) + $this->calculateDistanceScore($distanceMeters),
                    2
                );

                $board->distance_meters = $distanceMeters;
                $board->scene_signals = $sceneSignals;
                $board->ranking_score = $rankingScore;

                return $board;
            })
            ->sort(function (BulletinBoard $left, BulletinBoard $right) {
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
            'trusted_participants' => true,
            'scene_alignment' => true,
            'freshness' => true,
            'activity_health' => true,
            'distance' => true,
            'summary' => 'Nearby bulletin boards balance trusted recent participants, scene alignment, activity health, freshness, and distance without exposing private graph details.',
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    private function buildSceneSignals(User $viewer, BulletinBoard $board): ?array
    {
        return $this->aiMatchingService->getSceneSignalsForValues($viewer, array_filter([
            $board->name,
            $board->description,
            ...$board->recentMessages->take(5)->pluck('content')->all(),
        ]));
    }

    /**
     * @param  Collection<int, BulletinMessage>  $recentMessages
     */
    private function resolvePrimaryParticipantId(User $viewer, Collection $recentMessages, array $trustMap): ?int
    {
        $participantIds = $recentMessages
            ->pluck('user_id')
            ->filter(fn ($id) => is_int($id))
            ->unique()
            ->values();

        if ($participantIds->isEmpty()) {
            return null;
        }

        return $participantIds
            ->sortByDesc(function (int $participantId) use ($viewer, $trustMap) {
                return $this->localPulseRankingService->calculateCompositeScore(
                    $viewer,
                    $participantId,
                    null,
                    null,
                    $trustMap
                );
            })
            ->first();
    }

    private function resolveActivityTimestamp(BulletinBoard $board): ?CarbonInterface
    {
        $timestamp = $board->last_activity_at ?? $board->created_at ?? null;
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

    private function calculateHealthScore(BulletinBoard $board): float
    {
        $messageMomentum = min(8, ($board->message_count ?? 0) / 4);
        $activeUsersBoost = min(8, ($board->active_users ?? 0) * 2);
        $moderationPenalty = min(10, ($board->moderated_messages_count ?? 0) * 3);

        return max(0, round($messageMomentum + $activeUsersBoost - $moderationPenalty, 2));
    }

    private function calculateDistanceScore(int $distanceMeters): float
    {
        return max(0, 20 - min(20, $distanceMeters / 100));
    }
}
