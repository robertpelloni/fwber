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
        $subscriptions = Cache::tags(['subscriptions', "user:{$userId}"])
            ->remember("subscriptions:user:{$userId}", 300, function () use ($userId) {
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
        $cacheKey = "payments:history:user:{$userId}:page:{$page}";
        $payments = Cache::tags(['subscriptions', "user:{$userId}"])
            ->remember($cacheKey, 600, function () use ($userId) {
                return Payment::where('user_id', $userId)
                    ->orderBy('created_at', 'desc')
                    ->paginate(20);
            });

        return response()->json($payments);
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
