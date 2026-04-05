<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\UserMatch;
use App\Services\AIMatchingService;
use App\Services\ContentUnlockService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MatchInsightsController extends Controller
{
    public function __construct(
        private readonly AIMatchingService $aiMatchingService,
        private readonly ContentUnlockService $contentUnlockService,
    ) {}

    public function show(Request $request, int $targetUserId): JsonResponse
    {
        $user = $request->user();
        $target = User::query()->with('profile')->findOrFail($targetUserId);
        $this->assertActiveMatch($user, $target);

        $breakdown = $this->aiMatchingService->getCompatibilityBreakdown($user, $target);
        $cost = (int) config('economy.match_insights_unlock_cost', 10);

        if (! $this->contentUnlockService->hasUnlocked($user, 'match_insights', $target->id)) {
            return response()->json([
                'success' => true,
                'data' => [
                    'total_score' => $breakdown['total_score'] ?? 0,
                    'is_locked' => true,
                    'cost' => $cost,
                    'preview_message' => 'Unlock detailed compatibility analysis and AI insights for '.$cost.' tokens.',
                ],
            ]);
        }

        return response()->json([
            'success' => true,
            'data' => [
                ...$breakdown,
                'is_locked' => false,
            ],
        ]);
    }

    public function unlock(Request $request, int $targetUserId): JsonResponse
    {
        $user = $request->user()->fresh();
        $target = User::query()->findOrFail($targetUserId);
        $this->assertActiveMatch($user, $target);

        $result = $this->contentUnlockService->unlockMatchInsights($user, $target);

        return response()->json($result);
    }

    private function assertActiveMatch(User $user, User $target): void
    {
        $matched = UserMatch::query()
            ->where(function ($query) use ($user, $target): void {
                $query->where('user1_id', $user->id)->where('user2_id', $target->id);
            })
            ->orWhere(function ($query) use ($user, $target): void {
                $query->where('user1_id', $target->id)->where('user2_id', $user->id);
            })
            ->where('is_active', true)
            ->exists();

        abort_unless($matched, 403, 'You are not matched with this user.');
    }
}
