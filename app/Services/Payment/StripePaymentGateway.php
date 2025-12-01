<?php

namespace App\Services\Payment;

use Exception;

class StripePaymentGateway implements PaymentGatewayInterface
{
    protected $stripe;

    public function __construct()
    {
        // $this->stripe = new \Stripe\StripeClient(config('services.stripe.secret'));
    }

    public function createPaymentIntent(float $amount, string $currency, array $metadata = []): PaymentResult
    {
        try {
            // $intent = $this->stripe->paymentIntents->create([
            //     'amount' => (int) ($amount * 100),
            //     'currency' => $currency,
            //     'metadata' => $metadata,
            // ]);

            // return new PaymentResult(
            //     success: true,
            //     transactionId: $intent->id,
            //     data: ['client_secret' => $intent->client_secret]
            // );

            throw new Exception('Stripe driver not fully installed. Run "composer require stripe/stripe-php"');
        } catch (Exception $e) {
            return new PaymentResult(success: false, message: $e->getMessage());
        }
    }

    public function charge(float $amount, string $currency, string $source, array $metadata = []): PaymentResult
    {
        try {
            // $charge = $this->stripe->charges->create([
            //     'amount' => (int) ($amount * 100),
            //     'currency' => $currency,
            //     'source' => $source,
            //     'metadata' => $metadata,
            // ]);

            // return new PaymentResult(
            //     success: true,
            //     transactionId: $charge->id,
            //     data: $charge->toArray()
            // );

            throw new Exception('Stripe driver not fully installed. Run "composer require stripe/stripe-php"');
        } catch (Exception $e) {
            return new PaymentResult(success: false, message: $e->getMessage());
        }
    }

    public function refund(string $transactionId): PaymentResult
    {
        try {
            // $refund = $this->stripe->refunds->create(['charge' => $transactionId]);
            
            // return new PaymentResult(
            //     success: true,
            //     transactionId: $refund->id
            // );

            throw new Exception('Stripe driver not fully installed. Run "composer require stripe/stripe-php"');
        } catch (Exception $e) {
            return new PaymentResult(success: false, message: $e->getMessage());
        }
    }
}
