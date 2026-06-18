<?php

namespace App\Support\Integrations\Payment;

class PaymentRefundResult
{
    public function __construct(
        public readonly bool $success,
        public readonly string $status,                       // pending | processing | completed | failed
        public readonly ?string $providerRefundId = null,
        public readonly ?string $errorCode = null,
        public readonly ?string $errorMessage = null,
        public readonly array $raw = [],
    ) {}
}
