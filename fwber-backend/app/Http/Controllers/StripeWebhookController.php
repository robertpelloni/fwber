<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Models\Subscription;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Stripe\Exception\SignatureVerificationException;
use Stripe\Webhook;

class StripeWebhookController extends Controller
{
    /**
     * Minimal webhook surface restored for premium billing.
     *
     * We intentionally keep the handler compact and scoped to premium access so
     * production Stripe rollouts can complete without restoring every archived
     * economy/referral subsystem at once.
     */
    public function handleWebhook(Request $request): JsonResponse
    {
        $secret = config('services.stripe.webhook.secret') ?: config('services.stripe.webhook_secret');
        if (! $secret) {
            return response()->json(['error' => 'Stripe webhook secret is not configured.'], 503);
        }

        try {
            $event = Webhook::constructEvent(
                $request->getContent(),
                (string) $request->header('Stripe-Signature'),
                $secret
            );
        } catch (\UnexpectedValueException) {
            return response()->json(['error' => 'Invalid Stripe payload.'], 400);
        } catch (SignatureVerificationException) {
            return response()->json(['error' => 'Invalid Stripe signature.'], 400);
        }

        switch ($event->type) {
            case 'payment_intent.succeeded':
                $this->handlePaymentIntentSucceeded($event->data->object);
                break;
            case 'customer.subscription.updated':
                $this->handleSubscriptionUpdated($event->data->object);
                break;
            case 'customer.subscription.deleted':
                $this->handleSubscriptionDeleted($event->data->object);
                break;
        }

        return response()->json(['status' => 'success']);
    }

    protected function handlePaymentIntentSucceeded(object $paymentIntent): void
    {
        $userId = data_get($paymentIntent, 'metadata.user_id');
        $durationDays = (int) (data_get($paymentIntent, 'metadata.duration_days') ?: 30);
        $planName = (string) (data_get($paymentIntent, 'metadata.plan_name') ?: 'gold');
        $stripePrice = data_get($paymentIntent, 'metadata.stripe_price');

        if (! $userId) {
            return;
        }

        $user = User::find($userId);
        if (! $user) {
            return;
        }

        $expiresAt = Carbon::now()->addDays($durationDays);

        DB::transaction(function () use ($user, $paymentIntent, $expiresAt, $planName, $stripePrice) {
            $user->forceFill([
                'tier' => 'gold',
                'tier_expires_at' => $expiresAt,
                'unlimited_swipes' => true,
            ])->save();

            if (Schema::hasTable('payments') && ! Payment::where('transaction_id', $paymentIntent->id)->exists()) {
                Payment::create([
                    'user_id' => $user->id,
                    'amount' => ((float) $paymentIntent->amount) / 100,
                    'currency' => strtoupper((string) $paymentIntent->currency),
                    'payment_gateway' => 'stripe',
                    'transaction_id' => $paymentIntent->id,
                    'status' => 'succeeded',
                    'description' => (string) ($paymentIntent->description ?: 'Premium Subscription'),
                    'metadata' => method_exists($paymentIntent, 'toArray') ? $paymentIntent->toArray() : (array) $paymentIntent,
                ]);
            }

            if (Schema::hasTable('subscriptions')) {
                Subscription::updateOrCreate(
                    ['user_id' => $user->id, 'name' => $planName],
                    [
                        'stripe_id' => $paymentIntent->id,
                        'stripe_status' => 'active',
                        'stripe_price' => $stripePrice,
                        'quantity' => 1,
                        'ends_at' => $expiresAt,
                    ]
                );
            }
        });
    }

    protected function handleSubscriptionUpdated(object $stripeSubscription): void
    {
        if (! Schema::hasTable('subscriptions')) {
            return;
        }

        $subscription = Subscription::where('stripe_id', $stripeSubscription->id)->first();
        if (! $subscription) {
            return;
        }

        $subscription->update([
            'stripe_status' => (string) $stripeSubscription->status,
            'ends_at' => data_get($stripeSubscription, 'current_period_end')
                ? Carbon::createFromTimestamp((int) data_get($stripeSubscription, 'current_period_end'))
                : $subscription->ends_at,
        ]);

        $user = $subscription->user;
        if (! $user) {
            return;
        }

        if (in_array($stripeSubscription->status, ['active', 'trialing'], true)) {
            $user->forceFill([
                'tier' => 'gold',
                'tier_expires_at' => $subscription->ends_at,
                'unlimited_swipes' => true,
            ])->save();
        }
    }

    protected function handleSubscriptionDeleted(object $stripeSubscription): void
    {
        if (! Schema::hasTable('subscriptions')) {
            return;
        }

        $subscription = Subscription::where('stripe_id', $stripeSubscription->id)->first();
        if (! $subscription) {
            return;
        }

        $subscription->update([
            'stripe_status' => 'canceled',
            'ends_at' => now(),
        ]);

        $user = $subscription->user;
        if (! $user) {
            return;
        }

        $user->forceFill([
            'tier' => 'free',
            'tier_expires_at' => null,
            'unlimited_swipes' => false,
        ])->save();
    }
}
