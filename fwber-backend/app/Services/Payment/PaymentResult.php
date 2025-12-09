<?php

namespace App\Services\Payment;

class PaymentResult
{
    public function __construct(
        public bool $success,
        public ?string $transactionId = null,
        public ?string $message = null,
        public array $data = []
    ) {}
}
