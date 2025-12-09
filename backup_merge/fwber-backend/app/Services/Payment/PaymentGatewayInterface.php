<?php

namespace App\Services\Payment;

interface PaymentGatewayInterface
{
    /**
     * Create a payment intent (for client-side confirmation).
     *
     * @param float $amount
     * @param string $currency
     * @param array $metadata
     * @return PaymentResult
     */
    public function createPaymentIntent(float $amount, string $currency, array $metadata = []): PaymentResult;

    /**
     * Charge a source directly (server-side).
     *
     * @param float $amount
     * @param string $currency
     * @param string $source
     * @param array $metadata
     * @return PaymentResult
     */
    public function charge(float $amount, string $currency, string $source, array $metadata = []): PaymentResult;

    /**
     * Refund a transaction.
     *
     * @param string $transactionId
     * @return PaymentResult
     */
    public function refund(string $transactionId): PaymentResult;

    /**
     * Verify a payment status (e.g. after client-side confirmation).
     *
     * @param string $paymentId
     * @return PaymentResult
     */
    public function verifyPayment(string $paymentId): PaymentResult;
}
