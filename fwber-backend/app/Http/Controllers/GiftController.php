<?php

namespace App\Http\Controllers;

use App\Models\Gift;
use App\Models\User;
use App\Models\UserGift;
use App\Notifications\GiftReceivedNotification;
use App\Services\TokenDistributionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class GiftController extends Controller
{
    protected $tokenService;

    public function __construct(TokenDistributionService $tokenService)
    {
        $this->tokenService = $tokenService;
    }

    /**
     * List all available gifts.
     */
    public function index()
    {
        $gifts = Gift::where('is_active', true)
            ->orderBy('cost', 'asc')
            ->get();

        return response()->json(['data' => $gifts]);
    }

    /**
     * Send a gift to another user.
     */
    public function send(Request $request)
    {
        $request->validate([
            'receiver_id' => 'required|exists:users,id',
            'gift_id' => 'required|exists:gifts,id',
            'message' => 'nullable|string|max:255',
        ]);

        $sender = Auth::user();
        $receiver = User::findOrFail($request->receiver_id);
        $gift = Gift::findOrFail($request->gift_id);

        if ($sender->id === $receiver->id) {
            return response()->json(['error' => 'You cannot send gifts to yourself.'], 400);
        }

        if (!$gift->is_active) {
            return response()->json(['error' => 'This gift is no longer available.'], 400);
        }

        try {
            DB::transaction(function () use ($sender, $receiver, $gift, $request) {
                // Deduct tokens from Sender
                $this->tokenService->spendTokens(
                    $sender,
                    $gift->cost,
                    "Sent gift '{$gift->name}' to {$receiver->name}"
                );

                // Credit Receiver (P2P Transfer)
                $receiver->increment('token_balance', $gift->cost);

                // Record Transaction for Receiver
                \App\Models\TokenTransaction::create([
                    'user_id' => $receiver->id,
                    'amount' => $gift->cost,
                    'type' => 'gift_received',
                    'description' => "Received gift '{$gift->name}' from {$sender->name}",
                    'metadata' => ['sender_id' => $sender->id, 'gift_id' => $gift->id]
                ]);

                // Create UserGift record
                UserGift::create([
                    'sender_id' => $sender->id,
                    'receiver_id' => $receiver->id,
                    'gift_id' => $gift->id,
                    'message' => $request->message,
                    'cost_at_time' => $gift->cost,
                ]);

                // Send Notification to Receiver
                $receiver->notify(new GiftReceivedNotification($sender, $gift, $request->message));
            });

            return response()->json(['message' => 'Gift sent successfully!']);

        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    /**
     * List gifts received by the authenticated user.
     */
    public function received()
    {
        $gifts = UserGift::with(['sender', 'gift'])
            ->where('receiver_id', Auth::id())
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return response()->json($gifts);
    }
}
