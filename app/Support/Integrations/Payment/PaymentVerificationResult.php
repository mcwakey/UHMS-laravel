<?php

namespace App\Support\Integrations\Payment;

/**
 * Normalised result of verifying a provider transaction with the provider.
 *
 * `success` = the verification call itself succeeded.
 * `isPaid`  = the provider confirms the money was collected.
 */
class PaymentVerificationResult
{
    public function __construct(
        public readonly bool $success,
        public readonly string $status,                       // paid | pending | failed | cancelled | expired
        public readonly bool $isPaid = false,
        public readonly ?float $amount = null,
        public readonly ?string $currency = null,
        public readonly ?string $providerTransactionId = null,
        public readonly ?string $providerStatus = null,
        public readonly ?string $paidAt = null,
        public readonly ?string $errorCode = null,
        public readonly ?string $errorMessage = null,
        public readonly array $raw = [],
        public readonly ?int $httpStatus = null,
    ) {}
}
