<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Models\User;
use App\Services\Payment\PaymentGatewayInterface;
use App\Services\ReferralCommissionService;
use App\Support\PremiumPlanCatalog;
use App\Services\TokenDistributionService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PremiumController extends Controller
{
    protected $paymentGateway;

    protected $tokenService;

    protected $referralCommissionService;

    protected $premiumPlanCatalog;

    public function __construct(
        PaymentGatewayInterface $paymentGateway,
        TokenDistributionService $tokenService,
        ReferralCommissionService $referralCommissionService,
        PremiumPlanCatalog $premiumPlanCatalog
    )
    {
        $this->paymentGateway = $paymentGateway;
        $this->tokenService = $tokenService;
        $this->referralCommissionService = $referralCommissionService;
        $this->premiumPlanCatalog = $premiumPlanCatalog;
    }

    public function getWhoLikesYou(Request $request)
    {
        $user = $request->user();

        // Get users who liked the current user
        $likerIds = DB::table('match_actions')
            ->where('target_user_id', $user->id)
            ->where('action', 'like')
            ->pluck('user_id');

        $likers = User::with(['profile', 'photos'])->whereIn('id', $likerIds)->get();

        // Check premium status
        $isPremium = $user->tier === 'gold' &&
                     $user->tier_expires_at &&
                     Carbon::parse($user->tier_expires_at)->isFuture();

        if ($isPremium) {
            return response()->json($likers);
        }

        // If not premium, check which profiles have been unlocked via sharing
        $unlockedProfileIds = \App\Models\ShareUnlock::where('user_id', $user->id)
            ->whereIn('target_profile_id', $likerIds)
            ->pluck('target_profile_id')
            ->toArray();

        // Sanitize the response for non-premium users
        $sanitizedLikers = $likers->map(function ($liker) use ($unlockedProfileIds) {
            if (in_array($liker->id, $unlockedProfileIds)) {
                return $liker;
            }

            // Return obfuscated data for locked profiles
            return [
                'id' => $liker->id,
                'is_locked' => true,
                'age' => $liker->age, // Show age as a teaser
                'name' => 'Someone', // Hide name
                'avatar_url' => null, // Hide avatar
                'photos' => [], // Hide photos
                'blur_hash' => 'LEHV6nWB2yk8pyo0adR*.7kCMdnj', // Generic blur hash
            ];
        });

        return response()->json($sanitizedLikers);
    }

    public function initiatePurchase(Request $request)
    {
        $user = $request->user();
        $planId = $this->resolveRequestedPlanId($request);
        $plan = $this->premiumPlanCatalog->find($planId);

        if (! $plan) {
            return response()->json([
                'error' => "Unknown premium plan [{$planId}].",
            ], 422);
        }

        $amount = $plan['price_usd'];
        $currency = $plan['currency'];

        $result = $this->paymentGateway->createPaymentIntent($amount, $currency, [
            'user_id' => $user->id,
            'plan_id' => $plan['id'],
            'plan_name' => $plan['name'],
            'stripe_price' => $plan['stripe_price'],
            'duration_days' => $plan['duration_days'],
            'description' => $plan['description'],
        ]);

        if ($result->success) {
            return response()->json([
                ...$result->data,
                'plan_id' => $plan['id'],
                'plan' => [
                    'id' => $plan['id'],
                    'name' => $plan['name'],
                    'display_name' => $plan['display_name'],
                    'price_usd' => $plan['price_usd'],
                    'currency' => $plan['currency'],
                    'interval' => $plan['interval'],
                ],
            ]);
        }

        return response()->json(['error' => $result->message], 500);
    }

    public function purchasePremium(Request $request)
    {
        $user = $request->user();
        $paymentMethod = $request->input('payment_method', 'stripe');
        $requestedPlanId = $this->resolveRequestedPlanId($request);
        $plan = $this->premiumPlanCatalog->find($requestedPlanId);

        if (! $plan) {
            return response()->json([
                'error' => "Unknown premium plan [{$requestedPlanId}].",
            ], 422);
        }

        if ($paymentMethod === 'token') {
            $tokenCost = $plan['token_cost'];

            try {
                $subscription = null;
                DB::transaction(function () use ($user, $tokenCost, $plan, &$subscription) {
                    $this->tokenService->spendTokens($user, $tokenCost, "Purchased {$plan['display_name']}");

                    // Grant premium
                    $user->tier = 'gold';
                    $user->tier_expires_at = Carbon::now()->addDays($plan['duration_days']);
                    $user->unlimited_swipes = true;
                    $user->save();

                    // Create Subscription record
                    $subscription = \App\Models\Subscription::create([
                        'user_id' => $user->id,
                        'name' => $plan['name'],
                        'stripe_id' => 'token_'.uniqid(),
                        'stripe_status' => 'active',
                        'stripe_price' => $plan['stripe_price'],
                        'quantity' => 1,
                        'ends_at' => Carbon::now()->addDays($plan['duration_days']),
                    ]);

                    $this->referralCommissionService->awardPremiumCommissions($user->fresh(), $subscription, null, 'token');
                });

                return response()->json([
                    'message' => 'Premium purchased successfully',
                    'tier' => $user->tier,
                    'expires_at' => $user->tier_expires_at,
                ]);

            } catch (\Exception $e) {
                return response()->json(['error' => $e->getMessage()], 400);
            }
        }

        $amount = $plan['price_usd'];
        $currency = $plan['currency'];

        try {
            $planId = $plan['id'];

            if ($request->has('payment_intent_id')) {
                $result = $this->paymentGateway->verifyPayment($request->input('payment_intent_id'));
                $planId = $this->resolveRequestedPlanId($request, $result->data);
                $plan = $this->premiumPlanCatalog->find($planId);

                if (! $plan) {
                    return response()->json([
                        'error' => "Unknown premium plan [{$planId}].",
                    ], 422);
                }

                $amount = $plan['price_usd'];
                $currency = $plan['currency'];
            } else {
                $paymentMethodId = $request->input('payment_method_id');

                if (! $paymentMethodId) {
                    return response()->json([
                        'error' => 'A Stripe payment method or confirmed payment intent is required.',
                    ], 422);
                }

                $result = $this->paymentGateway->charge($amount, $currency, $paymentMethodId, [
                    'user_id' => $user->id,
                    'plan_id' => $plan['id'],
                    'plan_name' => $plan['name'],
                    'stripe_price' => $plan['stripe_price'],
                    'duration_days' => $plan['duration_days'],
                    'description' => $plan['description'],
                ]);
            }

            if ($result->success) {
                // Wrap payment + user update + subscription creation in transaction for atomicity
                $paymentRecord = null;
                $subscription = null;

                DB::transaction(function () use ($user, $amount, $currency, $result, $plan, &$paymentRecord, &$subscription) {
                    // Log payment
                    $paymentRecord = Payment::create([
                        'user_id' => $user->id,
                        'amount' => $amount,
                        'currency' => $currency,
                        'payment_gateway' => config('services.payment.driver', 'mock'),
                        'transaction_id' => $result->transactionId,
                        'status' => 'succeeded',
                        'description' => $plan['description'],
                        'metadata' => $result->data,
                    ]);

                    // Grant premium
                    $user->tier = 'gold';
                    $user->tier_expires_at = Carbon::now()->addDays($plan['duration_days']);
                    $user->unlimited_swipes = true;
                    $user->save();

                    // Create Subscription record
                    $subscription = \App\Models\Subscription::create([
                        'user_id' => $user->id,
                        'name' => $plan['name'],
                        'stripe_id' => $result->transactionId ?? 'manual_'.uniqid(),
                        'stripe_status' => 'active',
                        'stripe_price' => $plan['stripe_price'],
                        'quantity' => 1,
                        'ends_at' => Carbon::now()->addDays($plan['duration_days']),
                    ]);
                    $this->referralCommissionService->awardPremiumCommissions(
                        $user->fresh(),
                        $subscription,
                        $paymentRecord,
                        'stripe'
                    );
                });

                return response()->json([
                    'message' => 'Premium purchased successfully',
                    'tier' => $user->tier,
                    'expires_at' => $user->tier_expires_at,
                ]);
            } else {
                Payment::create([
                    'user_id' => $user->id,
                    'amount' => $amount,
                    'currency' => $currency,
                    'payment_gateway' => config('services.payment.driver', 'mock'),
                    'transaction_id' => null,
                    'status' => 'failed',
                    'description' => 'Premium Subscription Failed',
                    'metadata' => ['error' => $result->message],
                ]);

                return response()->json(['error' => 'Payment failed: '.$result->message], 400);
            }
        } catch (\Exception $e) {
            return response()->json(['error' => 'Payment error: '.$e->getMessage()], 500);
        }
    }

    public function getPremiumStatus(Request $request)
    {
        $user = $request->user();

        $isPremium = $user->tier === 'gold' &&
                     $user->tier_expires_at &&
                     Carbon::parse($user->tier_expires_at)->isFuture();

        return response()->json([
            'is_premium' => $isPremium,
            'tier' => $user->tier,
            'expires_at' => $user->tier_expires_at,
            'unlimited_swipes' => $user->unlimited_swipes,
        ]);
    }

    protected function resolveRequestedPlanId(Request $request, array $paymentData = []): string
    {
        return $request->input('plan_id')
            ?? data_get($paymentData, 'metadata.plan_id')
            ?? $this->premiumPlanCatalog->defaultPlanId();
    }
}
