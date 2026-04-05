<?php

namespace App\Http\Controllers;

use App\Http\Requests\PurchaseBoostRequest;
use App\Models\Boost;
use App\Models\Payment;
use App\Models\WalletTransaction;
use App\Services\Payment\PaymentGatewayInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class BoostController extends Controller
{
    public function __construct(private readonly PaymentGatewayInterface $paymentGateway)
    {
    }

    public function purchaseBoost(PurchaseBoostRequest $request): JsonResponse
    {
        $user = $request->user()->fresh();
        $activeBoost = Boost::query()->where('user_id', $user->id)->active()->first();

        if ($activeBoost) {
            return response()->json([
                'error' => 'You already have an active boost.',
            ], 400);
        }

        $type = $request->validated('type');
        $paymentMethod = $request->validated('payment_method', 'token');
        $durationMinutes = $type === 'super' ? 120 : 30;
        $tokenCost = $type === 'super' ? 100 : 50;
        $cashPrice = $type === 'super' ? 9.99 : 4.99;

        if ($paymentMethod === 'token') {
            if ((float) $user->token_balance < $tokenCost) {
                return response()->json([
                    'error' => 'Insufficient token balance.',
                ], 402);
            }

            $boost = DB::transaction(function () use ($user, $type, $durationMinutes, $tokenCost): Boost {
                $user->forceFill([
                    'token_balance' => round((float) $user->token_balance - $tokenCost, 2),
                ])->save();

                if (Schema::hasTable('wallet_transactions')) {
                    WalletTransaction::query()->create([
                        'user_id' => $user->id,
                        'amount' => -1 * $tokenCost,
                        'type' => 'boost_purchase',
                        'description' => sprintf('Purchased %s boost', ucfirst($type)),
                    ]);
                }

                return Boost::query()->create([
                    'user_id' => $user->id,
                    'started_at' => now(),
                    'expires_at' => now()->copy()->addMinutes($durationMinutes),
                    'boost_type' => $type,
                    'status' => 'active',
                ]);
            });

            return response()->json($boost);
        }

        $paymentMethodId = $request->input('payment_method_id', 'tok_mock_success');
        $result = $this->paymentGateway->charge($cashPrice, 'USD', $paymentMethodId, [
            'user_id' => (string) $user->id,
            'boost_type' => $type,
        ]);

        if (! $result->success) {
            if (Schema::hasTable('payments')) {
                Payment::query()->create([
                    'user_id' => $user->id,
                    'amount' => $cashPrice,
                    'currency' => 'USD',
                    'payment_gateway' => config('services.payment.driver', 'mock'),
                    'transaction_id' => $result->transactionId,
                    'status' => 'failed',
                    'description' => ucfirst($type).' boost failed',
                    'metadata' => [
                        'error' => $result->message,
                    ],
                ]);
            }

            return response()->json([
                'error' => $result->message ?: 'Payment failed.',
            ], 400);
        }

        $boost = DB::transaction(function () use ($user, $type, $durationMinutes, $cashPrice, $result): Boost {
            if (Schema::hasTable('payments')) {
                Payment::query()->create([
                    'user_id' => $user->id,
                    'amount' => $cashPrice,
                    'currency' => 'USD',
                    'payment_gateway' => config('services.payment.driver', 'mock'),
                    'transaction_id' => $result->transactionId ?: 'boost_'.Str::uuid(),
                    'status' => 'succeeded',
                    'description' => ucfirst($type).' boost',
                    'metadata' => $result->data,
                ]);
            }

            return Boost::query()->create([
                'user_id' => $user->id,
                'started_at' => now(),
                'expires_at' => now()->copy()->addMinutes($durationMinutes),
                'boost_type' => $type,
                'status' => 'active',
            ]);
        });

        return response()->json($boost);
    }

    public function getActiveBoost(Request $request): JsonResponse
    {
        $boost = Boost::query()
            ->where('user_id', $request->user()->id)
            ->active()
            ->latest('expires_at')
            ->first();

        return response()->json([
            'data' => $boost,
            'message' => $boost ? 'Active boost found.' : 'No active boost.',
        ]);
    }

    public function getBoostHistory(Request $request): JsonResponse
    {
        $boosts = Boost::query()
            ->where('user_id', $request->user()->id)
            ->latest()
            ->limit(20)
            ->get();

        return response()->json($boosts);
    }
}
