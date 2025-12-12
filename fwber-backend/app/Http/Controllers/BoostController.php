<?php

namespace App\Http\Controllers;

use App\Http\Requests\PurchaseBoostRequest;
use App\Models\Boost;
use App\Models\Payment;
use App\Services\Payment\PaymentGatewayInterface;
use App\Services\TokenDistributionService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use OpenApi\Attributes as OA;

class BoostController extends Controller
{
    protected $paymentGateway;
    protected $tokenService;

    public function __construct(PaymentGatewayInterface $paymentGateway, TokenDistributionService $tokenService)
    {
        $this->paymentGateway = $paymentGateway;
        $this->tokenService = $tokenService;
    }

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
    public function purchaseBoost(PurchaseBoostRequest $request): JsonResponse
    {
        $user = $request->user();

        // Check for existing active boost
        $existingBoost = Boost::where('user_id', $user->id)
            ->active()
            ->first();

        if ($existingBoost) {
            return response()->json(['error' => 'You already have an active boost'], 400);
        }

        $type = $request->type;
        $amount = $type === 'super' ? 9.99 : 4.99;
        $currency = 'USD';
        $paymentMethodId = $request->input('payment_method_id', 'tok_visa');

        try {
            $result = $this->paymentGateway->charge($amount, $currency, $paymentMethodId);

            if ($result->success) {
                // Wrap payment + boost creation in transaction for atomicity
                $boost = \DB::transaction(function () use ($user, $amount, $currency, $result, $type) {
                    // Log payment
                    Payment::create([
                        'user_id' => $user->id,
                        'amount' => $amount,
                        'currency' => $currency,
                        'payment_gateway' => config('services.payment.driver', 'mock'),
                        'transaction_id' => $result->transactionId,
                        'status' => 'succeeded',
                        'description' => ucfirst($type) . ' Boost',
                        'metadata' => $result->data,
                    ]);

                    $duration = $type === 'super' ? 120 : 30; // minutes
                    $now = now();

                    return Boost::create([
                        'user_id' => $user->id,
                        'started_at' => $now,
                        'expires_at' => $now->copy()->addMinutes($duration),
                        'boost_type' => $type,
                        'status' => 'active',
                    ]);
                });

                return response()->json($boost);
            } else {
                 Payment::create([
                    'user_id' => $user->id,
                    'amount' => $amount,
                    'currency' => $currency,
                    'payment_gateway' => config('services.payment.driver', 'mock'),
                    'transaction_id' => null,
                    'status' => 'failed',
                    'description' => ucfirst($type) . ' Boost Failed',
                    'metadata' => ['error' => $result->message],
                ]);

                return response()->json(['error' => 'Payment failed: ' . $result->message], 400);
            }
        } catch (\Exception $e) {
            return response()->json(['error' => 'Payment error: ' . $e->getMessage()], 500);
        }
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
