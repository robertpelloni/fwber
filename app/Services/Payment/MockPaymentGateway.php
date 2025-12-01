<?php

namespace App\Services\Payment;

use Illuminate\Support\Str;

class MockPaymentGateway implements PaymentGatewayInterface
{
    public function createPaymentIntent(float $amount, string $currency, array $metadata = []): PaymentResult
    {
        // Simulate a successful intent creation
        return new PaymentResult(
            success: true,
            transactionId: 'pi_mock_' . Str::random(24),
            data: [
                'client_secret' => 'pi_mock_secret_' . Str::random(24),
                'amount' => $amount,
                'currency' => $currency,
            ]
        );
    }

    public function charge(float $amount, string $currency, string $source, array $metadata = []): PaymentResult
    {
        // Simulate failure for specific source
        if ($source === 'tok_fail') {
            return new PaymentResult(
                success: false,
                message: 'Card declined'
            );
        }

        return new PaymentResult(
            success: true,
            transactionId: 'ch_mock_' . Str::random(24),
            message: 'Payment successful',
            data: [
                'amount' => $amount,
                'currency' => $currency,
                'source' => $source,
            ]
        );
    }

    public function refund(string $transactionId): PaymentResult
    {
        return new PaymentResult(
            success: true,
            transactionId: 're_mock_' . Str::random(24),
            message: 'Refund successful'
        );
    }

    public function verifyPayment(string $paymentId): PaymentResult
    {
        return new PaymentResult(
            success: true,
            transactionId: $paymentId,
            message: 'Payment verified (mock)',
            data: ['status' => 'succeeded']
        );
    }
}
