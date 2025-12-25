<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CreatorSubscription;
use App\Models\User;
use App\Services\TokenDistributionService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class CreatorSubscriptionController extends Controller
{
    protected $tokenService;

    public function __construct(TokenDistributionService $tokenService)
    {
        $this->tokenService = $tokenService;
    }

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

        $price = $creator->profile->subscription_price;
        if (!$price || $price <= 0) {
            return response()->json(['error' => 'This user has not enabled subscriptions'], 400);
        }

        // Check for existing active subscription
        $existing = CreatorSubscription::where('user_id', $user->id)
            ->where('creator_id', $creator->id)
            ->active()
            ->first();

        if ($existing) {
            return response()->json(['message' => 'Already subscribed', 'expires_at' => $existing->expires_at], 200);
        }

        try {
            DB::transaction(function () use ($user, $creator, $price) {
                // Deduct from User
                $this->tokenService->spendTokens($user, $price, "Subscribed to {$creator->name}");

                // Credit Creator
                $this->tokenService->awardTokens($creator, $price, 'subscription_revenue', "Subscription from {$user->name}");

                // Create Subscription (30 days)
                CreatorSubscription::create([
                    'user_id' => $user->id,
                    'creator_id' => $creator->id,
                    'expires_at' => now()->addDays(30),
                    'cost' => $price,
                ]);
            });

            return response()->json(['message' => 'Subscribed successfully', 'expires_at' => now()->addDays(30)]);

        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    /**
     * Check subscription status
     */
    public function check(Request $request, int $creatorId): JsonResponse
    {
        $user = $request->user();
        $subscription = CreatorSubscription::where('user_id', $user->id)
            ->where('creator_id', $creatorId)
            ->active()
            ->first();

        return response()->json([
            'is_subscribed' => (bool)$subscription,
            'expires_at' => $subscription?->expires_at,
        ]);
    }
}
