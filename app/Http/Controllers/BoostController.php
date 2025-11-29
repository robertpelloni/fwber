<?php

namespace App\Http\Controllers;

use App\Models\Boost;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use OpenApi\Attributes as OA;

class BoostController extends Controller
{
    /**
     * Purchase a profile boost
     *
     * @OA\Post(
     *     path="/boosts/purchase",
     *     summary="Purchase a profile boost",
     *     tags={"Boosts"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"type"},
     *             @OA\Property(property="type", type="string", enum={"standard", "super"})
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Boost purchased successfully",
     *         @OA\JsonContent(ref="#/components/schemas/Boost")
     *     ),
     *     @OA\Response(response=400, description="User already has an active boost")
     * )
     */
    public function purchaseBoost(Request $request): JsonResponse
    {
        $request->validate([
            'type' => 'required|in:standard,super',
        ]);

        $user = $request->user();

        // Check for existing active boost
        $existingBoost = Boost::where('user_id', $user->id)
            ->active()
            ->first();

        if ($existingBoost) {
            return response()->json(['error' => 'You already have an active boost'], 400);
        }

        $duration = $request->type === 'super' ? 120 : 30; // minutes
        $now = now();

        // In a real app, we would handle payment processing here.
        // For this implementation, we assume payment is successful or handled elsewhere.

        $boost = Boost::create([
            'user_id' => $user->id,
            'started_at' => $now,
            'expires_at' => $now->copy()->addMinutes($duration),
            'boost_type' => $request->type,
            'status' => 'active',
        ]);

        return response()->json($boost);
    }

    /**
     * Get active boost
     *
     * @OA\Get(
     *     path="/boosts/active",
     *     summary="Get user's active boost",
     *     tags={"Boosts"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Active boost details",
     *         @OA\JsonContent(ref="#/components/schemas/Boost")
     *     ),
     *     @OA\Response(response=404, description="No active boost found")
     * )
     */
    public function getActiveBoost(Request $request): JsonResponse
    {
        $boost = Boost::where('user_id', $request->user()->id)
            ->active()
            ->first();

        if (!$boost) {
            return response()->json(['message' => 'No active boost'], 404);
        }

        return response()->json($boost);
    }

    /**
     * Get boost history
     *
     * @OA\Get(
     *     path="/boosts/history",
     *     summary="Get user's boost history",
     *     tags={"Boosts"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="List of past boosts",
     *         @OA\JsonContent(type="array", @OA\Items(ref="#/components/schemas/Boost"))
     *     )
     * )
     */
    public function getBoostHistory(Request $request): JsonResponse
    {
        $boosts = Boost::where('user_id', $request->user()->id)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($boosts);
    }
}
