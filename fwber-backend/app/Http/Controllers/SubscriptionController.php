<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Models\Subscription;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

class SubscriptionController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/subscriptions",
     *     summary="Get user's subscriptions",
     *     tags={"Subscriptions"},
     *     security={{"sanctum":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="List of user's subscriptions",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(ref="#/components/schemas/Subscription")
     *         )
     *     )
     * )
     */
    public function index(Request $request)
    {
        $userId = Auth::id();
        
        // Cache for 5 minutes with user-specific tagged caching
        $cacheKey = config('optimization.cache_version') . ":subscriptions:user:{$userId}";
        $subscriptions = Cache::tags(['subscriptions', "user:{$userId}"])
            ->remember($cacheKey, 300, function () use ($userId) {
                return Subscription::where('user_id', $userId)
                    ->orderBy('created_at', 'desc')
                    ->get();
            });

        return response()->json($subscriptions);
    }

    /**
     * @OA\Get(
     *     path="/api/subscriptions/history",
     *     summary="Get user's payment history",
     *     tags={"Subscriptions"},
     *     security={{"sanctum":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Paginated list of payments",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/Payment")),
     *             @OA\Property(property="current_page", type="integer"),
     *             @OA\Property(property="per_page", type="integer"),
     *             @OA\Property(property="total", type="integer")
     *         )
     *     )
     * )
     */
    public function history(Request $request)
    {
        $userId = Auth::id();
        $page = $request->input('page', 1);
        
        // Cache for 10 minutes with user-specific and page-specific tagged caching
        $cacheKey = config('optimization.cache_version') . ":payments:history:user:{$userId}:page:{$page}";
        $payments = Cache::tags(['subscriptions', "user:{$userId}"])
            ->remember($cacheKey, 600, function () use ($userId) {
                return Payment::where('user_id', $userId)
                    ->orderBy('created_at', 'desc')
                    ->paginate(20);
            });

        return response()->json($payments);
    }

    /**
     * @OA\Post(
     *     path="/api/subscriptions/cancel",
     *     summary="Cancel active subscription",
     *     tags={"Subscriptions"},
     *     security={{"sanctum":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Subscription canceled successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string"),
     *             @OA\Property(property="ends_at", type="string", format="date-time")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="No active subscription found"
     *     )
     * )
     */
    public function cancel(Request $request)
    {
        $user = $request->user();
        
        $subscription = Subscription::where('user_id', $user->id)
            ->where('stripe_status', 'active')
            ->first();

        if (!$subscription) {
            return response()->json(['message' => 'No active subscription found.'], 400);
        }

        // Call Stripe API to cancel
        try {
            $stripe = new \Stripe\StripeClient(config('services.stripe.secret'));
            $stripe->subscriptions->cancel($subscription->stripe_id, []);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Stripe cancellation failed: ' . $e->getMessage()], 500);
        }

        $subscription->stripe_status = 'canceled';
        // We keep ends_at as is, so user has access until the end of the period
        $subscription->save();

        // Invalidate cache
        Cache::tags(['subscriptions', "user:{$user->id}"])->flush();

        return response()->json([
            'message' => 'Subscription canceled successfully. You will retain access until ' . $subscription->ends_at->format('Y-m-d'),
            'ends_at' => $subscription->ends_at
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // Currently handled by PremiumController@purchasePremium
        // In the future, this could handle creating recurring subscriptions
        return response()->json(['message' => 'Please use /api/premium/purchase to upgrade.'], 400);
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        $subscription = Subscription::where('user_id', Auth::id())->findOrFail($id);
        return response()->json($subscription);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Subscription $subscription)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Subscription $subscription)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Subscription $subscription)
    {
        //
    }
}
