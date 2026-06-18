<?php

namespace App\Support\Integrations\Payment;

class PaymentRefundRequest
{
    public function __construct(
        public readonly string $refundReference,
        public readonly string $providerTransactionId,
        public readonly float $amount,
        public readonly string $currency,
        public readonly ?string $reason = null,
        public readonly array $metadata = [],
    ) {}
}
