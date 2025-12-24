<?php

namespace App\Http\Controllers;

use App\Models\CreatorSubscription;
use App\Models\TokenTransaction;
use App\Models\User;
use App\Services\TokenDistributionService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class CreatorSubscriptionController extends Controller
{
    public function __construct(
        private TokenDistributionService $tokenService
    ) {}

    /**
     * Subscribe to a creator
     */
    public function subscribe(Request $request, int $creatorId): JsonResponse
    {
        $user = $request->user();
        $creator = User::with('profile')->findOrFail($creatorId);

        if ($user->id === $creator->id) {
            return response()->json(['error' => 'Cannot subscribe to yourself'], 400);
        }

        $price = $creator->profile->subscription_price ?? 0;
        if ($price <= 0) {
             return response()->json(['error' => 'User does not offer subscriptions'], 400);
        }

        // Check if already subscribed
        $existing = CreatorSubscription::where('user_id', $user->id)
            ->where('creator_id', $creator->id)
            ->active()
            ->first();

        if ($existing) {
             return response()->json(['message' => 'Already subscribed', 'expires_at' => $existing->expires_at], 200);
        }

        try {
             DB::transaction(function() use ($user, $creator, $price) {
                 $this->tokenService->spendTokens($user, $price, "Subscribed to {$creator->name}");
                 $creator->increment('token_balance', $price);

                 TokenTransaction::create([
                     'user_id' => $creator->id,
                     'amount' => $price,
                     'type' => 'subscription_income',
                     'description' => "Subscription from {$user->name}",
                     'metadata' => ['subscriber_id' => $user->id]
                 ]);

                 CreatorSubscription::updateOrCreate(
                     ['user_id' => $user->id, 'creator_id' => $creator->id],
                     [
                         'expires_at' => now()->addDays(30),
                         'cost' => $price,
                         'status' => 'active'
                     ]
                 );
             });
        } catch (\Exception $e) {
             return response()->json(['error' => $e->getMessage()], 400);
        }

        return response()->json(['message' => 'Subscribed successfully', 'expires_at' => now()->addDays(30)]);
    }

    /**
     * Check subscription status
     */
    public function check(Request $request, int $creatorId): JsonResponse
    {
        $user = $request->user();

        $sub = CreatorSubscription::where('user_id', $user->id)
            ->where('creator_id', $creatorId)
            ->active()
            ->first();

        return response()->json([
            'is_subscribed' => (bool)$sub,
            'expires_at' => $sub?->expires_at,
        ]);
    }
}
