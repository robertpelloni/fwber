<?php

namespace App\Services;

use App\Models\Chatroom;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class ChatroomRankingService
{
    public function __construct(
        private readonly AIMatchingService $aiMatchingService,
        private readonly LocalPulseRankingService $localPulseRankingService,
    ) {
    }

    /**
     * @param  Collection<int, Chatroom>  $chatrooms
     * @return Collection<int, Chatroom>
     */
    public function rankBrowse(User $viewer, Collection $chatrooms, string $sortBy = 'activity'): Collection
    {
        if ($chatrooms->isEmpty()) {
            return $chatrooms;
        }

        $chatrooms->loadMissing(['creator.profile']);

        $trustMap = $this->localPulseRankingService->buildTrustMap(
            $viewer,
            $chatrooms->pluck('created_by')->filter(fn ($id) => is_int($id))->unique()->values()->all()
        );

        return $chatrooms
            ->map(function (Chatroom $chatroom) use ($viewer, $trustMap) {
                $sceneSignals = $this->buildSceneSignals($viewer, $chatroom);
                $chatroom->scene_signals = $sceneSignals;
                $chatroom->ranking_score = round(
                    $this->localPulseRankingService->calculateCompositeScore(
                        $viewer,
                        $chatroom->created_by,
                        $this->resolveActivityTimestamp($chatroom),
                        $sceneSignals,
                        $trustMap
                    ) + $this->calculateCommunityHealthScore($chatroom),
                    2
                );

                return $chatroom;
            })
            ->sort(function (Chatroom $left, Chatroom $right) use ($sortBy) {
                $scoreComparison = ($right->ranking_score <=> $left->ranking_score);
                if ($scoreComparison !== 0) {
                    return $scoreComparison;
                }

                return $this->compareByPreferredSort($left, $right, $sortBy);
            })
            ->values();
    }

    public function buildRankingStrategy(): array
    {
        return [
            'trusted_creators' => true,
            'scene_alignment' => true,
            'community_health' => true,
            'freshness' => true,
            'summary' => 'Chatroom discovery balances trusted creators, scene alignment, community health, and freshness without exposing private graph details.',
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    private function buildSceneSignals(User $viewer, Chatroom $chatroom): ?array
    {
        return $this->aiMatchingService->getSceneSignalsForValues($viewer, array_filter([
            $chatroom->name,
            $chatroom->description,
            $chatroom->category,
            $chatroom->type,
            $chatroom->city,
            $chatroom->neighborhood,
            $chatroom->creator?->profile?->bio,
        ]));
    }

    private function calculateCommunityHealthScore(Chatroom $chatroom): float
    {
        $memberBoost = min(10, (float) $chatroom->member_count * 1.5);
        $messageBoost = min(8, (float) $chatroom->message_count / 12);
        $publicBoost = $chatroom->is_public ? 2.0 : 0.0;

        return round($memberBoost + $messageBoost + $publicBoost, 2);
    }

    private function resolveActivityTimestamp(Chatroom $chatroom): ?CarbonInterface
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

    private function compareByPreferredSort(Chatroom $left, Chatroom $right, string $sortBy): int
    {
        return match ($sortBy) {
            'newest' => (($right->created_at?->getTimestamp() ?? 0) <=> ($left->created_at?->getTimestamp() ?? 0)),
            'most_members' => (($right->member_count ?? 0) <=> ($left->member_count ?? 0)),
            default => (($right->last_activity_at?->getTimestamp() ?? 0) <=> ($left->last_activity_at?->getTimestamp() ?? 0)),
        };
    }
}
