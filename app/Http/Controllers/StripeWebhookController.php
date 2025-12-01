<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Stripe\Stripe;
use Stripe\Webhook;
use Stripe\Exception\SignatureVerificationException;
use App\Models\User;
use App\Models\Payment;
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
            // ... handle other event types
            default:
                // Unexpected event type
                // Log::info('Received unknown event type ' . $event->type);
        }

        return response()->json(['status' => 'success']);
    }

    protected function handlePaymentIntentSucceeded($paymentIntent)
    {
        $userId = $paymentIntent->metadata->user_id ?? null;

        if (!$userId) {
            Log::warning('Stripe Webhook: No user_id in metadata for PaymentIntent ' . $paymentIntent->id);
            return;
        }

        $user = User::find($userId);

        if (!$user) {
            Log::error('Stripe Webhook: User not found for ID ' . $userId);
            return;
        }

        // Check if payment already recorded to avoid duplicates
        $existingPayment = Payment::where('transaction_id', $paymentIntent->id)->first();
        if ($existingPayment) {
            Log::info('Stripe Webhook: Payment already recorded for ' . $paymentIntent->id);
            return;
        }

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

        Log::info("Stripe Webhook: Premium granted to user {$user->id}");
    }
}
