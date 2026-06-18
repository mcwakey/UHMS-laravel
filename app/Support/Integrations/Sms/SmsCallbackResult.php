<?php

namespace App\Support\Integrations\Sms;

/**
 * Normalised view of an inbound SMS provider callback / delivery report.
 */
class SmsCallbackResult
{
    public function __construct(
        public readonly ?string $providerMessageId = null,
        public readonly ?string $status = null,          // delivered | undelivered | failed | sent
        public readonly ?string $providerStatus = null,
        public readonly ?string $reportedAt = null,
        public readonly ?string $eventType = 'delivery_report',
        public readonly bool $signatureValid = false,
        public readonly array $raw = [],
    ) {}
}
