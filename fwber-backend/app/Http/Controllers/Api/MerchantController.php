<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MerchantPayment;
use App\Models\User;
use App\Models\TokenTransaction;
use App\Services\TokenDistributionService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class MerchantController extends Controller
{
    protected $tokenService;

    public function __construct(TokenDistributionService $tokenService)
    {
        $this->tokenService = $tokenService;
    }

    /**
     * Generate or regenerate merchant secret
     */
    public function generateKeys(Request $request): JsonResponse
    {
        $user = $request->user();

        $secret = 'sk_' . Str::random(32);

        $user->merchant_secret = $secret;
        $user->merchant_name = $request->input('merchant_name', $user->merchant_name ?? $user->name);
        $user->save();

        return response()->json(['merchant_secret' => $secret, 'merchant_name' => $user->merchant_name]);
    }

    /**
     * Create a payment intent (Called by Merchant Server)
     */
    public function createPayment(Request $request): JsonResponse
    {
        $secret = $request->header('X-Merchant-Secret');
        if (!$secret) {
            return response()->json(['error' => 'Missing X-Merchant-Secret header'], 401);
        }

        $merchant = User::where('merchant_secret', $secret)->first();
        if (!$merchant) {
            return response()->json(['error' => 'Invalid merchant secret'], 401);
        }

        $request->validate([
            'amount' => 'required|numeric|min:1',
            'description' => 'required|string|max:255',
            'redirect_url' => 'required|url',
            'webhook_url' => 'nullable|url',
        ]);

        $payment = MerchantPayment::create([
            'merchant_id' => $merchant->id,
            'amount' => $request->amount,
            'description' => $request->description,
            'redirect_url' => $request->redirect_url,
            'webhook_url' => $request->webhook_url,
            'status' => 'pending',
            'metadata' => $request->input('metadata'),
        ]);

        // Construct Checkout URL (assuming frontend runs on same domain or configured URL)
        // For local dev, hardcode or use config
        $baseUrl = config('app.frontend_url', 'http://localhost:3000');
        $checkoutUrl = "{$baseUrl}/pay/{$payment->id}";

        return response()->json([
            'id' => $payment->id,
            'checkout_url' => $checkoutUrl,
        ]);
    }

    /**
     * Get payment details (Called by Checkout Page)
     */
    public function show(string $id): JsonResponse
    {
        $payment = MerchantPayment::with('merchant:id,name,merchant_name')->findOrFail($id);

        return response()->json([
            'payment' => $payment,
            'merchant_name' => $payment->merchant->merchant_name ?? $payment->merchant->name,
        ]);
    }

    /**
     * Confirm payment (Called by Payer via Checkout Page)
     */
    public function confirm(Request $request, string $id): JsonResponse
    {
        $user = $request->user();
        $payment = MerchantPayment::with('merchant')->findOrFail($id);

        if ($payment->status !== 'pending') {
            return response()->json(['error' => 'Payment already processed'], 400);
        }

        if ($user->token_balance < $payment->amount) {
            return response()->json(['error' => 'Insufficient balance'], 400);
        }

        if ($user->id === $payment->merchant_id) {
             return response()->json(['error' => 'Cannot pay yourself'], 400);
        }

        try {
            DB::transaction(function () use ($user, $payment) {
                // Deduct from User
                $this->tokenService->spendTokens($user, $payment->amount, "Payment to {$payment->merchant->name}: {$payment->description}");

                // Credit Merchant
                $this->tokenService->awardTokens($payment->merchant, $payment->amount, 'merchant_sale', "Sale: {$payment->description}");

                $payment->update([
                    'status' => 'paid',
                    'payer_id' => $user->id,
                    'paid_at' => now(),
                ]);
            });

            // Trigger Webhook (Fire and forget style ideally, but sync for now)
            if ($payment->webhook_url) {
                try {
                    Http::timeout(2)->post($payment->webhook_url, [
                        'event' => 'payment.succeeded',
                        'payment' => $payment->fresh(),
                    ]);
                } catch (\Exception $e) {
                    \Log::error('Merchant webhook failed', ['url' => $payment->webhook_url, 'error' => $e->getMessage()]);
                }
            }

            return response()->json([
                'success' => true,
                'redirect_url' => $payment->redirect_url,
            ]);

        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }
}
