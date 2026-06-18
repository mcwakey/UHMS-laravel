<?php

namespace App\Support\Integrations\Sms;

class SmsStatusResult
{
    public function __construct(
        public readonly bool $success,
        public readonly ?string $status = null,          // delivered | undelivered | sent | failed | pending
        public readonly ?string $providerStatus = null,
        public readonly ?string $deliveredAt = null,
        public readonly array $raw = [],
        public readonly ?string $errorMessage = null,
    ) {}
}
