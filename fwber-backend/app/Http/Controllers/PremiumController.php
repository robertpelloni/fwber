<?php

namespace App\Http\Controllers;

use App\Models\MatchAction;
use App\Models\Payment;
use App\Models\Subscription;
use App\Models\User;
use App\Services\Payment\PaymentGatewayInterface;
use App\Services\ReferralCommissionService;
use App\Support\PremiumPlanCatalog;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class PremiumController extends Controller
{
    public function __construct(
        protected PaymentGatewayInterface $paymentGateway,
        protected PremiumPlanCatalog $premiumPlanCatalog,
        protected ReferralCommissionService $referralCommissionService,
    ) {}

    public function plans(): JsonResponse
    {
        return response()->json([
            'plans' => array_values(array_map(
                fn (array $plan) => [
                    'id' => $plan['id'],
                    'name' => $plan['name'],
                    'display_name' => $plan['display_name'],
                    'description' => $plan['description'],
                    'price_usd' => $plan['price_usd'],
                    'currency' => $plan['currency'],
                    'duration_days' => $plan['duration_days'],
                    'interval' => $plan['interval'],
                ],
                array_map(fn (string $id, array $plan) => ['id' => $id] + $plan, array_keys($this->premiumPlanCatalog->plans()), $this->premiumPlanCatalog->plans())
            )),
        ]);
    }

    public function getPremiumStatus(Request $request): JsonResponse
    {
        $user = $request->user();

        return response()->json([
            'is_premium' => $this->isPremium($user),
            'tier' => $user->tier ?: 'free',
            'expires_at' => $user->tier_expires_at,
            'unlimited_swipes' => (bool) $user->unlimited_swipes,
            'active_plan' => $this->activeSubscriptionSummary($user),
        ]);
    }

    public function history(Request $request): JsonResponse
    {
        $user = $request->user();

        $payments = Schema::hasTable('payments')
            ? Payment::query()
                ->where('user_id', $user->id)
                ->latest()
                ->limit(20)
                ->get(['id', 'amount', 'currency', 'payment_gateway', 'transaction_id', 'status', 'description', 'created_at'])
            : collect();

        $subscriptions = Schema::hasTable('subscriptions')
            ? Subscription::query()
                ->where('user_id', $user->id)
                ->latest()
                ->limit(10)
                ->get(['id', 'name', 'stripe_status', 'stripe_price', 'ends_at', 'created_at'])
            : collect();

        return response()->json([
            'payments' => $payments,
            'subscriptions' => $subscriptions,
        ]);
    }

    public function initiatePurchase(Request $request): JsonResponse
    {
        $user = $request->user();
        $plan = $this->resolvePlan($request->input('plan_id'));
        if ($plan instanceof JsonResponse) {
            return $plan;
        }

        $result = $this->paymentGateway->createPaymentIntent($plan['price_usd'], $plan['currency'], [
            'user_id' => (string) $user->id,
            'plan_id' => $plan['id'],
            'plan_name' => $plan['name'],
            'stripe_price' => $plan['stripe_price'],
            'duration_days' => (string) $plan['duration_days'],
            'description' => $plan['description'],
        ]);

        if (! $result->success) {
            return response()->json([
                'error' => $result->message ?: 'Unable to initiate premium purchase.',
            ], 500);
        }

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

    public function purchasePremium(Request $request): JsonResponse
    {
        $user = $request->user();
        $plan = $this->resolvePlan($request->input('plan_id'));
        if ($plan instanceof JsonResponse) {
            return $plan;
        }

        $paymentIntentId = $request->string('payment_intent_id')->toString();
        $paymentMethodId = $request->string('payment_method_id')->toString();
        $paymentDriver = config('services.payment.driver', 'mock');

        if ($paymentIntentId !== '') {
            $result = $this->paymentGateway->verifyPayment($paymentIntentId);
        } elseif ($paymentMethodId !== '') {
            $result = $this->paymentGateway->charge($plan['price_usd'], $plan['currency'], $paymentMethodId, [
                'user_id' => (string) $user->id,
                'plan_id' => $plan['id'],
                'plan_name' => $plan['name'],
                'stripe_price' => $plan['stripe_price'],
                'duration_days' => (string) $plan['duration_days'],
                'description' => $plan['description'],
            ]);
        } elseif ($paymentDriver === 'mock') {
            // The restored premium page must work in local/dev/test environments
            // without real Stripe credentials. In mock mode we intentionally run a
            // simulated charge so the billing UX and schema path stay testable.
            $result = $this->paymentGateway->charge($plan['price_usd'], $plan['currency'], 'tok_mock_success', [
                'user_id' => (string) $user->id,
                'plan_id' => $plan['id'],
                'plan_name' => $plan['name'],
                'stripe_price' => $plan['stripe_price'],
                'duration_days' => (string) $plan['duration_days'],
                'description' => $plan['description'],
            ]);
        } else {
            return response()->json([
                'error' => 'A confirmed payment intent or payment method is required for live premium purchases.',
            ], 422);
        }

        if (! $result->success) {
            $this->recordFailedPayment($user, $plan, $result->message, $result->transactionId, $result->data);

            return response()->json([
                'error' => $result->message ?: 'Payment failed.',
            ], 400);
        }

        $paymentRecord = null;
        $subscriptionRecord = null;

        $expiresAt = DB::transaction(function () use ($user, $plan, $result, $paymentDriver, &$paymentRecord, &$subscriptionRecord) {
            $expiresAt = Carbon::now()->addDays($plan['duration_days']);

            $user->forceFill([
                'tier' => 'gold',
                'tier_expires_at' => $expiresAt,
                'unlimited_swipes' => true,
            ])->save();

            if (Schema::hasTable('payments')) {
                $paymentRecord = Payment::create([
                    'user_id' => $user->id,
                    'amount' => $plan['price_usd'],
                    'currency' => $plan['currency'],
                    'payment_gateway' => $paymentDriver,
                    'transaction_id' => $result->transactionId ?: 'premium_'.Str::uuid(),
                    'status' => 'succeeded',
                    'description' => $plan['description'],
                    'metadata' => [
                        'plan_id' => $plan['id'],
                        'gateway_response' => $result->data,
                    ],
                ]);
            }

            if (Schema::hasTable('subscriptions')) {
                $subscriptionRecord = Subscription::updateOrCreate(
                    ['user_id' => $user->id, 'name' => $plan['name']],
                    [
                        'stripe_id' => $result->transactionId ?: 'premium_'.Str::uuid(),
                        'stripe_status' => 'active',
                        'stripe_price' => $plan['stripe_price'],
                        'quantity' => 1,
                        'ends_at' => $expiresAt,
                    ]
                );
            }

            return $expiresAt;
        });

        $this->referralCommissionService->awardPremiumCommissions(
            $user->fresh(),
            $subscriptionRecord,
            $paymentRecord,
            $paymentDriver,
        );

        return response()->json([
            'message' => 'Premium purchased successfully',
            'plan' => $plan['name'],
            'tier' => 'gold',
            'expires_at' => $expiresAt,
        ]);
    }

    public function getWhoLikesYou(Request $request): JsonResponse
    {
        $user = $request->user();

        if (! $this->isPremium($user)) {
            return response()->json([
                'message' => 'Premium required',
                'locked' => true,
                'count' => MatchAction::where('target_user_id', $user->id)
                    ->where('action', 'like')
                    ->count(),
            ], 403);
        }

        $likerIds = MatchAction::query()
            ->where('target_user_id', $user->id)
            ->where('action', 'like')
            ->latest()
            ->pluck('user_id')
            ->unique()
            ->values();

        $users = User::query()
            ->with(['profile', 'photos'])
            ->whereIn('id', $likerIds)
            ->get()
            ->map(function (User $likedUser) {
                $profile = $likedUser->profile;
                $primaryPhoto = $likedUser->photos->sortBy('order')->first();

                return [
                    'id' => $likedUser->id,
                    'name' => $profile?->display_name ?: $likedUser->name,
                    'display_name' => $profile?->display_name,
                    'age' => $profile?->age,
                    'bio' => $profile?->bio,
                    'avatarUrl' => $primaryPhoto?->path ? asset('storage/'.$primaryPhoto->path) : $likedUser->avatar_url,
                    'photo_url' => $primaryPhoto?->path ? asset('storage/'.$primaryPhoto->path) : $likedUser->avatar_url,
                ];
            })
            ->values();

        return response()->json([
            'users' => $users,
        ]);
    }

    protected function resolvePlan(?string $planId): array|JsonResponse
    {
        $plan = $this->premiumPlanCatalog->find($planId);

        if (! $plan) {
            return response()->json([
                'error' => 'Unknown premium plan.',
            ], 422);
        }

        return $plan;
    }

    protected function isPremium(User $user): bool
    {
        return ($user->tier ?: 'free') === 'gold'
            && $user->tier_expires_at !== null
            && Carbon::parse($user->tier_expires_at)->isFuture();
    }

    protected function activeSubscriptionSummary(User $user): ?array
    {
        if (! Schema::hasTable('subscriptions')) {
            return null;
        }

        $subscription = Subscription::query()
            ->where('user_id', $user->id)
            ->whereIn('stripe_status', ['active', 'trialing'])
            ->latest('ends_at')
            ->first();

        if (! $subscription) {
            return null;
        }

        return [
            'name' => $subscription->name,
            'status' => $subscription->stripe_status,
            'ends_at' => $subscription->ends_at,
        ];
    }

    protected function recordFailedPayment(User $user, array $plan, ?string $message, ?string $transactionId, array $metadata = []): void
    {
        if (! Schema::hasTable('payments')) {
            return;
        }

        Payment::create([
            'user_id' => $user->id,
            'amount' => $plan['price_usd'],
            'currency' => $plan['currency'],
            'payment_gateway' => config('services.payment.driver', 'mock'),
            'transaction_id' => $transactionId,
            'status' => 'failed',
            'description' => 'Premium purchase failed',
            'metadata' => [
                'plan_id' => $plan['id'],
                'error' => $message,
                'gateway_response' => $metadata,
            ],
        ]);
    }
}
