<?php

namespace App\Support\Integrations\Payment;

class PaymentInitiationResult
{
    public function __construct(
        public readonly bool $success,
        public readonly string $status,                       // initiated | pending | requires_customer_action | failed
        public readonly ?string $providerTransactionId = null,
        public readonly ?string $providerStatus = null,
        public readonly ?string $redirectUrl = null,
        public readonly ?string $instructions = null,
        public readonly ?string $errorCode = null,
        public readonly ?string $errorMessage = null,
        public readonly array $raw = [],
        public readonly ?int $httpStatus = null,
    ) {}
}
