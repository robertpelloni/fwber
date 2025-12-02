<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Stripe\Stripe;
use Stripe\Webhook;
use Stripe\Exception\SignatureVerificationException;
use App\Models\User;
use App\Models\Payment;
use App\Models\Subscription;
use App\Support\LogContext;
use Carbon\Carbon;

class StripeWebhookController extends Controller
{
    public function handleWebhook(Request $request)
    {
        $payload = $request->getContent();
        $sig_header = $request->header('Stripe-Signature');
        $endpoint_secret = config('services.stripe.webhook_secret');

        try {
            $event = Webhook::constructEvent(
                $payload, $sig_header, $endpoint_secret
            );
        } catch(\UnexpectedValueException $e) {
            // Invalid payload
            Log::error('Stripe Webhook Error: Invalid Payload');
            return response()->json(['error' => 'Invalid Payload'], 400);
        } catch(SignatureVerificationException $e) {
            // Invalid signature
            Log::error('Stripe Webhook Error: Invalid Signature');
            return response()->json(['error' => 'Invalid Signature'], 400);
        }

        // Handle the event
        switch ($event->type) {
            case 'payment_intent.succeeded':
                $paymentIntent = $event->data->object;
                $this->handlePaymentIntentSucceeded($paymentIntent);
                break;
            case 'customer.subscription.created':
                $subscription = $event->data->object;
                $this->handleSubscriptionCreated($subscription);
                break;
            case 'customer.subscription.updated':
                $subscription = $event->data->object;
                $this->handleSubscriptionUpdated($subscription);
                break;
            case 'customer.subscription.deleted':
                $subscription = $event->data->object;
                $this->handleSubscriptionDeleted($subscription);
                break;
            case 'invoice.payment_failed':
                $invoice = $event->data->object;
                $this->handleInvoicePaymentFailed($invoice);
                break;
            case 'invoice.payment_succeeded':
                $invoice = $event->data->object;
                $this->handleInvoicePaymentSucceeded($invoice);
                break;
            default:
                // Log unknown event types for monitoring
                Log::info('Stripe Webhook: Received unknown event type ' . $event->type);
        }

        return response()->json(['status' => 'success']);
    }

    protected function handlePaymentIntentSucceeded($paymentIntent)
    {
        $userId = $paymentIntent->metadata->user_id ?? null;

        if (!$userId) {
            Log::warning('Stripe Webhook: No user_id in metadata for PaymentIntent ' . $paymentIntent->id, LogContext::fromWebhook(
                webhookId: $paymentIntent->id,
                eventType: 'payment_intent_missing_user',
                extra: ['payment_intent_id' => $paymentIntent->id]
            ));
            return;
        }

        $user = User::find($userId);

        if (!$user) {
            Log::error('Stripe Webhook: User not found for ID ' . $userId, LogContext::fromWebhook(
                webhookId: $paymentIntent->id,
                eventType: 'payment_user_not_found',
                extra: ['user_id' => $userId, 'payment_intent_id' => $paymentIntent->id]
            ));
            return;
        }

        // Check if payment already recorded to avoid duplicates
        $existingPayment = Payment::where('transaction_id', $paymentIntent->id)->first();
        if ($existingPayment) {
            Log::info('Stripe Webhook: Payment already recorded for ' . $paymentIntent->id, LogContext::fromWebhook(
                webhookId: $paymentIntent->id,
                eventType: 'payment_duplicate',
                extra: ['payment_intent_id' => $paymentIntent->id]
            ));
            return;
        }

        // Wrap payment + user update + subscription creation in transaction for atomicity
        \DB::transaction(function () use ($user, $paymentIntent) {
            // Log Payment
            Payment::create([
                'user_id' => $user->id,
                'amount' => $paymentIntent->amount / 100, // Convert cents to dollars
                'currency' => $paymentIntent->currency,
                'payment_gateway' => 'stripe',
                'transaction_id' => $paymentIntent->id,
                'status' => 'succeeded',
                'description' => $paymentIntent->description ?? 'Premium Subscription',
                'metadata' => $paymentIntent->toArray(),
            ]);

            // Grant Premium
            $user->tier = 'gold';
            $user->tier_expires_at = Carbon::now()->addDays(30);
            $user->unlimited_swipes = true;
            $user->save();

            // Create Subscription record
            \App\Models\Subscription::create([
                'user_id' => $user->id,
                'name' => 'gold',
                'stripe_id' => $paymentIntent->id,
                'stripe_status' => 'active',
                'stripe_price' => 'price_premium_monthly',
                'quantity' => 1,
                'ends_at' => Carbon::now()->addDays(30),
            ]);
        });

        Log::info("Stripe Webhook: Premium granted to user {$user->id}", LogContext::fromPayment(
            transactionId: $paymentIntent->id,
            gateway: 'stripe',
            extra: [
                'tier' => 'gold',
                'expires_at' => $user->tier_expires_at->toIso8601String()
            ]
        ));
        
        // Invalidate subscription cache for this user
        Cache::tags(['subscriptions', "user:{$user->id}"])->flush();
    }

    /**
     * Handle subscription created event
     */
    protected function handleSubscriptionCreated($stripeSubscription)
    {
        $customerId = $stripeSubscription->customer;
        $userId = $stripeSubscription->metadata->user_id ?? null;

        if (!$userId) {
            Log::warning('Stripe Webhook: No user_id in subscription metadata for ' . $stripeSubscription->id, LogContext::fromWebhook(
                webhookId: $stripeSubscription->id,
                eventType: 'subscription_missing_user',
                extra: ['stripe_subscription_id' => $stripeSubscription->id]
            ));
            return;
        }

        $user = User::find($userId);
        if (!$user) {
            Log::error('Stripe Webhook: User not found for ID ' . $userId, LogContext::fromWebhook(
                webhookId: $stripeSubscription->id,
                eventType: 'subscription_user_not_found',
                extra: ['user_id' => $userId, 'stripe_subscription_id' => $stripeSubscription->id]
            ));
            return;
        }

        // Check if subscription already exists
        $existing = Subscription::where('stripe_id', $stripeSubscription->id)->first();
        if ($existing) {
            Log::info('Stripe Webhook: Subscription already exists ' . $stripeSubscription->id, LogContext::fromWebhook(
                webhookId: $stripeSubscription->id,
                eventType: 'subscription_duplicate',
                extra: ['stripe_subscription_id' => $stripeSubscription->id]
            ));
            return;
        }

        // Wrap subscription creation + user update in transaction for atomicity
        \DB::transaction(function () use ($user, $stripeSubscription) {
            // Create subscription record
            Subscription::create([
                'user_id' => $user->id,
                'name' => $stripeSubscription->metadata->plan_name ?? 'gold',
                'stripe_id' => $stripeSubscription->id,
                'stripe_status' => $stripeSubscription->status,
                'stripe_price' => $stripeSubscription->items->data[0]->price->id ?? null,
                'quantity' => $stripeSubscription->items->data[0]->quantity ?? 1,
                'trial_ends_at' => $stripeSubscription->trial_end ? Carbon::createFromTimestamp($stripeSubscription->trial_end) : null,
                'ends_at' => $stripeSubscription->current_period_end ? Carbon::createFromTimestamp($stripeSubscription->current_period_end) : null,
            ]);

            // Grant premium tier if subscription is active
            if ($stripeSubscription->status === 'active') {
                $user->tier = 'gold';
                $user->tier_expires_at = Carbon::createFromTimestamp($stripeSubscription->current_period_end);
                $user->unlimited_swipes = true;
                $user->save();
            }
        });

        Log::info("Stripe Webhook: Subscription created for user {$user->id}", LogContext::fromWebhook(
            webhookId: $stripeSubscription->id,
            eventType: 'subscription_created',
            extra: [
                'user_id' => $user->id,
                'status' => $stripeSubscription->status,
                'plan' => $stripeSubscription->metadata->plan_name ?? 'gold'
            ]
        ));
        
        // Invalidate subscription cache
        Cache::tags(['subscriptions', "user:{$user->id}"])->flush();
    }

    /**
     * Handle subscription updated event
     */
    protected function handleSubscriptionUpdated($stripeSubscription)
    {
        $subscription = Subscription::where('stripe_id', $stripeSubscription->id)->first();
        
        if (!$subscription) {
            Log::warning('Stripe Webhook: Subscription not found ' . $stripeSubscription->id, LogContext::fromWebhook(
                webhookId: $stripeSubscription->id,
                eventType: 'subscription_not_found_update',
                extra: ['stripe_subscription_id' => $stripeSubscription->id]
            ));
            return;
        }

        $user = $subscription->user;
        if (!$user) {
            Log::error('Stripe Webhook: User not found for subscription ' . $stripeSubscription->id, LogContext::fromWebhook(
                webhookId: $stripeSubscription->id,
                eventType: 'subscription_update_user_not_found',
                extra: ['stripe_subscription_id' => $stripeSubscription->id]
            ));
            return;
        }

        // Update subscription record
        $subscription->update([
            'stripe_status' => $stripeSubscription->status,
            'quantity' => $stripeSubscription->items->data[0]->quantity ?? 1,
            'trial_ends_at' => $stripeSubscription->trial_end ? Carbon::createFromTimestamp($stripeSubscription->trial_end) : null,
            'ends_at' => $stripeSubscription->current_period_end ? Carbon::createFromTimestamp($stripeSubscription->current_period_end) : null,
        ]);

        // Update user tier based on subscription status
        if (in_array($stripeSubscription->status, ['active', 'trialing'])) {
            $user->tier = 'gold';
            $user->tier_expires_at = Carbon::createFromTimestamp($stripeSubscription->current_period_end);
            $user->unlimited_swipes = true;
            $user->save();
        } elseif (in_array($stripeSubscription->status, ['past_due', 'canceled', 'unpaid'])) {
            // Revoke premium access
            $user->tier = 'free';
            $user->tier_expires_at = null;
            $user->unlimited_swipes = false;
            $user->save();
        }

        Log::info("Stripe Webhook: Subscription updated for user {$user->id}, status: {$stripeSubscription->status}", LogContext::fromWebhook(
            webhookId: $stripeSubscription->id,
            eventType: 'subscription_updated',
            extra: [
                'user_id' => $user->id,
                'status' => $stripeSubscription->status,
                'tier' => $user->tier
            ]
        ));
        
        // Invalidate subscription cache
        Cache::tags(['subscriptions', "user:{$user->id}"])->flush();
    }

    /**
     * Handle subscription deleted/canceled event
     */
    protected function handleSubscriptionDeleted($stripeSubscription)
    {
        $subscription = Subscription::where('stripe_id', $stripeSubscription->id)->first();
        
        if (!$subscription) {
            Log::warning('Stripe Webhook: Subscription not found for deletion ' . $stripeSubscription->id, LogContext::fromWebhook(
                webhookId: $stripeSubscription->id,
                eventType: 'subscription_not_found_delete',
                extra: ['stripe_subscription_id' => $stripeSubscription->id]
            ));
            return;
        }

        $user = $subscription->user;
        if (!$user) {
            Log::error('Stripe Webhook: User not found for subscription deletion ' . $stripeSubscription->id, LogContext::fromWebhook(
                webhookId: $stripeSubscription->id,
                eventType: 'subscription_delete_user_not_found',
                extra: ['stripe_subscription_id' => $stripeSubscription->id]
            ));
            return;
        }

        // Update subscription status to canceled
        $subscription->update([
            'stripe_status' => 'canceled',
            'ends_at' => now(),
        ]);

        // Revoke premium access immediately
        $user->tier = 'free';
        $user->tier_expires_at = null;
        $user->unlimited_swipes = false;
        $user->save();

        Log::info("Stripe Webhook: Subscription canceled for user {$user->id}", LogContext::fromWebhook(
            webhookId: $stripeSubscription->id,
            eventType: 'subscription_canceled',
            extra: ['user_id' => $user->id]
        ));
        
        // Invalidate subscription cache
        Cache::tags(['subscriptions', "user:{$user->id}"])->flush();
    }

    /**
     * Handle invoice payment failed event
     */
    protected function handleInvoicePaymentFailed($invoice)
    {
        $subscriptionId = $invoice->subscription;
        
        if (!$subscriptionId) {
            Log::warning('Stripe Webhook: No subscription ID in failed invoice ' . $invoice->id, LogContext::fromWebhook(
                webhookId: $invoice->id,
                eventType: 'invoice_missing_subscription',
                extra: ['invoice_id' => $invoice->id]
            ));
            return;
        }

        $subscription = Subscription::where('stripe_id', $subscriptionId)->first();
        
        if (!$subscription) {
            Log::warning('Stripe Webhook: Subscription not found for failed invoice ' . $subscriptionId, LogContext::fromWebhook(
                webhookId: $invoice->id,
                eventType: 'invoice_failed_subscription_not_found',
                extra: ['subscription_id' => $subscriptionId, 'invoice_id' => $invoice->id]
            ));
            return;
        }

        $user = $subscription->user;
        if (!$user) {
            Log::error('Stripe Webhook: User not found for failed invoice', LogContext::fromWebhook(
                webhookId: $invoice->id,
                eventType: 'invoice_failed_user_not_found',
                extra: ['subscription_id' => $subscriptionId, 'invoice_id' => $invoice->id]
            ));
            return;
        }

        // Update subscription status
        $subscription->update([
            'stripe_status' => 'past_due',
        ]);

        // Log the failed payment
        Payment::create([
            'user_id' => $user->id,
            'amount' => $invoice->amount_due / 100,
            'currency' => $invoice->currency,
            'payment_gateway' => 'stripe',
            'transaction_id' => $invoice->id,
            'status' => 'failed',
            'description' => 'Subscription renewal failed - Invoice ' . $invoice->number,
            'metadata' => [
                'invoice_id' => $invoice->id,
                'subscription_id' => $subscriptionId,
                'attempt_count' => $invoice->attempt_count,
            ],
        ]);

        // Keep premium active for grace period but log the issue
        Log::warning("Stripe Webhook: Invoice payment failed for user {$user->id}, subscription {$subscriptionId}", LogContext::fromWebhook(
            webhookId: $invoice->id,
            eventType: 'invoice_payment_failed',
            extra: [
                'user_id' => $user->id,
                'subscription_id' => $subscriptionId,
                'amount' => $invoice->amount_due / 100,
                'attempt_count' => $invoice->attempt_count
            ]
        ));
        
        // Invalidate subscription cache
        Cache::tags(['subscriptions', "user:{$user->id}"])->flush();
        
        // TODO: Send notification to user about failed payment
    }

    /**
     * Handle invoice payment succeeded event
     */
    protected function handleInvoicePaymentSucceeded($invoice)
    {
        $subscriptionId = $invoice->subscription;
        
        if (!$subscriptionId) {
            // One-time payment, already handled by payment_intent.succeeded
            return;
        }

        $subscription = Subscription::where('stripe_id', $subscriptionId)->first();
        
        if (!$subscription) {
            Log::warning('Stripe Webhook: Subscription not found for successful invoice ' . $subscriptionId, LogContext::fromWebhook(
                webhookId: $invoice->id,
                eventType: 'invoice_succeeded_subscription_not_found',
                extra: ['subscription_id' => $subscriptionId, 'invoice_id' => $invoice->id]
            ));
            return;
        }

        $user = $subscription->user;
        if (!$user) {
            Log::error('Stripe Webhook: User not found for successful invoice', LogContext::fromWebhook(
                webhookId: $invoice->id,
                eventType: 'invoice_succeeded_user_not_found',
                extra: ['subscription_id' => $subscriptionId, 'invoice_id' => $invoice->id]
            ));
            return;
        }

        // Check if payment already recorded
        $existingPayment = Payment::where('transaction_id', $invoice->payment_intent)->first();
        if (!$existingPayment) {
            // Log the successful renewal payment
            Payment::create([
                'user_id' => $user->id,
                'amount' => $invoice->amount_paid / 100,
                'currency' => $invoice->currency,
                'payment_gateway' => 'stripe',
                'transaction_id' => $invoice->payment_intent,
                'status' => 'succeeded',
                'description' => 'Subscription renewal - Invoice ' . $invoice->number,
                'metadata' => [
                    'invoice_id' => $invoice->id,
                    'subscription_id' => $subscriptionId,
                ],
            ]);
        }

        Log::info("Stripe Webhook: Subscription renewal successful for user {$user->id}", LogContext::fromPayment(
            transactionId: $invoice->payment_intent,
            gateway: 'stripe',
            extra: [
                'user_id' => $user->id,
                'subscription_id' => $subscriptionId,
                'amount' => $invoice->amount_paid / 100
            ]
        ));
        
        // Invalidate subscription cache
        Cache::tags(['subscriptions', "user:{$user->id}"])->flush();
    }
}
