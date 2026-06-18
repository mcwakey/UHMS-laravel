<?php

namespace App\Support\Integrations\Payment;

/**
 * Normalised request to initiate an online / mobile-money payment.
 */
class PaymentInitiationRequest
{
    public function __construct(
        public readonly string $paymentReference,
        public readonly float $amount,
        public readonly string $currency,
        public readonly ?string $payerName = null,
        public readonly ?string $payerPhone = null,
        public readonly ?string $payerEmail = null,
        public readonly ?string $paymentMethod = null,
        public readonly ?string $description = null,
        public readonly ?string $callbackUrl = null,
        public readonly array $metadata = [],
    ) {}
}
