<?php

namespace App\Support\Integrations\Payment;

/**
 * Normalised view of an inbound payment provider callback / webhook.
 *
 * A callback is never trusted on its own for amount; the gateway re-verifies
 * with the provider (when supported) before creating a UHMS payment.
 */
class PaymentCallbackResult
{
    public function __construct(
        public readonly ?string $paymentReference = null,
        public readonly ?string $providerTransactionId = null,
        public readonly ?string $status = null,               // paid | failed | pending | cancelled | expired
        public readonly ?string $providerStatus = null,
        public readonly ?float $amount = null,
        public readonly ?string $currency = null,
        public readonly ?string $eventType = null,
        public readonly bool $signatureValid = false,
        public readonly array $raw = [],
    ) {}
}
