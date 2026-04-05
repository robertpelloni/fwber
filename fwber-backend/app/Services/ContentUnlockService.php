<?php

namespace App\Services;

use App\Models\ContentUnlock;
use App\Models\Photo;
use App\Models\PhotoUnlock;
use App\Models\User;
use App\Models\WalletTransaction;
use App\Notifications\PhotoUnlockedNotification;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ContentUnlockService
{
    public function hasUnlocked(User $user, string $contentType, string|int $contentId): bool
    {
        if (! Schema::hasTable('content_unlocks')) {
            return false;
        }

        return ContentUnlock::query()
            ->where('user_id', $user->id)
            ->where('content_type', $contentType)
            ->where('content_id', (string) $contentId)
            ->exists();
    }

    public function unlockPhoto(User $viewer, Photo $photo): array
    {
        if ($viewer->id === $photo->user_id) {
            return [
                'message' => 'Your own photos are already available to you.',
                'already_unlocked' => true,
                'balance' => round((float) $viewer->token_balance, 2),
            ];
        }

        $cost = (float) ($photo->unlock_price ?? config('economy.photo_unlock_cost', 50));

        if ($this->hasUnlocked($viewer, 'photo', $photo->id)) {
            return [
                'message' => 'Photo already unlocked.',
                'already_unlocked' => true,
                'balance' => round((float) $viewer->token_balance, 2),
            ];
        }

        $this->assertBalance($viewer, $cost);

        DB::transaction(function () use ($viewer, $photo, $cost): void {
            $this->spendTokens($viewer, $cost, 'photo_unlock', sprintf('Unlocked private photo #%d', $photo->id));

            if (Schema::hasTable('photo_unlocks')) {
                PhotoUnlock::query()->firstOrCreate([
                    'user_id' => $viewer->id,
                    'photo_id' => $photo->id,
                ], [
                    'cost' => $cost,
                    'unlocked_at' => now(),
                ]);
            }

            if (Schema::hasTable('content_unlocks')) {
                ContentUnlock::query()->firstOrCreate([
                    'user_id' => $viewer->id,
                    'content_type' => 'photo',
                    'content_id' => (string) $photo->id,
                ], [
                    'cost' => $cost,
                ]);
            }
        });

        try {
            $photo->user?->notify(new PhotoUnlockedNotification($viewer, $photo));
        } catch (\Throwable $exception) {
            // Notifications should not make token unlocks fail.
        }

        return [
            'message' => 'Photo unlocked successfully.',
            'balance' => round((float) $viewer->fresh()->token_balance, 2),
        ];
    }

    public function unlockMatchInsights(User $viewer, User $target): array
    {
        $cost = (float) config('economy.match_insights_unlock_cost', 10);

        if ($this->hasUnlocked($viewer, 'match_insights', $target->id)) {
            return [
                'message' => 'Insights already unlocked.',
                'already_unlocked' => true,
                'balance' => round((float) $viewer->token_balance, 2),
            ];
        }

        $this->assertBalance($viewer, $cost);

        DB::transaction(function () use ($viewer, $target, $cost): void {
            $this->spendTokens($viewer, $cost, 'match_insights_unlock', sprintf('Unlocked AI compatibility insights for %s', $target->name));

            if (Schema::hasTable('content_unlocks')) {
                ContentUnlock::query()->firstOrCreate([
                    'user_id' => $viewer->id,
                    'content_type' => 'match_insights',
                    'content_id' => (string) $target->id,
                ], [
                    'cost' => $cost,
                ]);
            }
        });

        return [
            'message' => 'Unlocked successfully.',
            'balance' => round((float) $viewer->fresh()->token_balance, 2),
        ];
    }

    private function assertBalance(User $user, float $cost): void
    {
        if ((float) $user->token_balance < $cost) {
            throw new HttpResponseException(response()->json([
                'error' => 'Insufficient token balance.',
            ], 402));
        }
    }

    private function spendTokens(User $user, float $amount, string $type, string $description): void
    {
        $user->forceFill([
            'token_balance' => round((float) $user->token_balance - $amount, 2),
        ])->save();

        if (Schema::hasTable('wallet_transactions')) {
            WalletTransaction::query()->create([
                'user_id' => $user->id,
                'amount' => -1 * $amount,
                'type' => $type,
                'description' => $description,
            ]);
        }
    }
}
