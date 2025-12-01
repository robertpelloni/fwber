<?php

namespace App\Services\Payment;

use Exception;
use Stripe\StripeClient;

class StripePaymentGateway implements PaymentGatewayInterface
{
    protected $stripe;

    public function __construct()
    {
        $this->stripe = new StripeClient(config('services.stripe.secret'));
    }

    public function createPaymentIntent(float $amount, string $currency, array $metadata = []): PaymentResult
    {
        try {
            $intent = $this->stripe->paymentIntents->create([
                'amount' => (int) ($amount * 100),
                'currency' => $currency,
                'metadata' => $metadata,
                'automatic_payment_methods' => [
                    'enabled' => true,
                ],
            ]);

            return new PaymentResult(
                success: true,
                transactionId: $intent->id,
                data: [
                    'client_secret' => $intent->client_secret,
                    'amount' => $amount,
                    'currency' => $currency,
                ]
            );
        } catch (Exception $e) {
            return new PaymentResult(success: false, message: $e->getMessage());
        }
    }

    public function charge(float $amount, string $currency, string $source, array $metadata = []): PaymentResult
    {
        try {
            // If source starts with 'tok_', use charges (legacy)
            if (str_starts_with($source, 'tok_')) {
                $charge = $this->stripe->charges->create([
                    'amount' => (int) ($amount * 100),
                    'currency' => $currency,
                    'source' => $source,
                    'metadata' => $metadata,
                ]);

                return new PaymentResult(
                    success: true,
                    transactionId: $charge->id,
                    data: $charge->toArray()
                );
            } else {
                // Assume it's a PaymentMethod ID and try to confirm immediately
                // Note: This might fail if 3DS is required.
                $intent = $this->stripe->paymentIntents->create([
                    'amount' => (int) ($amount * 100),
                    'currency' => $currency,
                    'payment_method' => $source,
                    'confirm' => true,
                    'metadata' => $metadata,
                    'automatic_payment_methods' => [
                        'enabled' => true,
                        'allow_redirects' => 'never'
                    ],
                ]);

                return new PaymentResult(
                    success: true,
                    transactionId: $intent->id,
                    data: $intent->toArray()
                );
            }
        } catch (Exception $e) {
            return new PaymentResult(success: false, message: $e->getMessage());
        }
    }

    public function refund(string $transactionId): PaymentResult
    {
        try {
            // Try as PaymentIntent first
            $refund = $this->stripe->refunds->create(['payment_intent' => $transactionId]);
            
            return new PaymentResult(
                success: true,
                transactionId: $refund->id
            );
        } catch (Exception $e) {
            // Fallback for Charge ID
            try {
                $refund = $this->stripe->refunds->create(['charge' => $transactionId]);
                return new PaymentResult(
                    success: true,
                    transactionId: $refund->id
                );
            } catch (Exception $e2) {
                return new PaymentResult(success: false, message: $e->getMessage());
            }
        }
    }

    public function verifyPayment(string $paymentId): PaymentResult
    {
        try {
            $intent = $this->stripe->paymentIntents->retrieve($paymentId);
            
            if ($intent->status === 'succeeded') {
                return new PaymentResult(
                    success: true,
                    transactionId: $intent->id,
                    data: $intent->toArray()
                );
            }
            
            return new PaymentResult(
                success: false, 
                message: 'Payment not succeeded. Status: ' . $intent->status,
                data: $intent->toArray()
            );
        } catch (Exception $e) {
            return new PaymentResult(success: false, message: $e->getMessage());
        }
    }
}
