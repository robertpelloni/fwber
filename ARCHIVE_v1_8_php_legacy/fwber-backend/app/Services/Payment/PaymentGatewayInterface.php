<?php

namespace App\Services\Payment;

interface PaymentGatewayInterface
{
    /**
     * Create a payment intent (for client-side confirmation).
     */
    public function createPaymentIntent(float $amount, string $currency, array $metadata = []): PaymentResult;

    /**
     * Charge a source directly (server-side).
     */
    public function charge(float $amount, string $currency, string $source, array $metadata = []): PaymentResult;

    /**
     * Refund a transaction.
     */
    public function refund(string $transactionId): PaymentResult;

    /**
     * Verify a payment status (e.g. after client-side confirmation).
     */
    public function verifyPayment(string $paymentId): PaymentResult;
}
