<?php

namespace App\Http\Controllers;

use App\Http\Requests\Gift\SendGiftRequest;
use App\Models\Gift;
use App\Models\UserGift;
use App\Models\WalletTransaction;
use App\Notifications\GiftReceivedNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class GiftController extends Controller
{
    /**
     * Return the active gift catalog used by chat/profile gifting surfaces.
     */
    public function index(): JsonResponse
    {
        $gifts = Gift::query()
            ->where('is_active', true)
            ->orderBy('cost')
            ->get();

        return response()->json([
            'data' => $gifts,
        ]);
    }

    /**
     * Execute a compact token-funded gift transfer.
     *
     * The old token economy was much broader, but the remaining live UI only
     * really needs a safe token debit, a receive-side credit, a gift ledger row,
     * and a user-visible notification. We deliberately keep the restored flow
     * small and deployment-safe.
     */
    public function send(SendGiftRequest $request): JsonResponse
    {
        $sender = $request->user()->fresh();
        $receiver = $request->user()->newQuery()->findOrFail($request->integer('receiver_id'));
        $gift = Gift::query()->where('is_active', true)->findOrFail($request->integer('gift_id'));

        if ($sender->id === $receiver->id) {
            return response()->json([
                'error' => 'You cannot send a gift to yourself.',
            ], 422);
        }

        if ((float) $sender->token_balance < (float) $gift->cost) {
            return response()->json([
                'error' => 'Insufficient token balance.',
            ], 402);
        }

        DB::transaction(function () use ($sender, $receiver, $gift, $request): void {
            $sender->forceFill([
                'token_balance' => round((float) $sender->token_balance - (float) $gift->cost, 2),
            ])->save();

            $receiver->forceFill([
                'token_balance' => round((float) $receiver->token_balance + (float) $gift->cost, 2),
            ])->save();

            if (Schema::hasTable('wallet_transactions')) {
                WalletTransaction::query()->create([
                    'user_id' => $sender->id,
                    'amount' => -1 * (float) $gift->cost,
                    'type' => 'gift_sent',
                    'description' => sprintf("Sent %s to %s", $gift->name, $receiver->name),
                ]);

                WalletTransaction::query()->create([
                    'user_id' => $receiver->id,
                    'amount' => (float) $gift->cost,
                    'type' => 'gift_received',
                    'description' => sprintf("Received %s from %s", $gift->name, $sender->name),
                ]);
            }

            UserGift::query()->create([
                'sender_id' => $sender->id,
                'receiver_id' => $receiver->id,
                'gift_id' => $gift->id,
                'message' => $request->validated('message'),
                'cost_at_time' => $gift->cost,
            ]);

            $receiver->notify(new GiftReceivedNotification($sender, $gift, $request->validated('message')));
        });

        return response()->json([
            'message' => 'Gift sent successfully!',
        ]);
    }

    public function received(Request $request): JsonResponse
    {
        $gifts = UserGift::query()
            ->with(['sender:id,name,avatar_url', 'gift'])
            ->where('receiver_id', $request->user()->id)
            ->latest()
            ->paginate(20);

        return response()->json($gifts);
    }
}
