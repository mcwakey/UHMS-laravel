<?php

namespace App\Support\Integrations\Sms;

/**
 * Normalised SMS send result.
 *
 * `perRecipient` is keyed by normalised phone number and carries:
 *   ['provider_message_id' => ?string, 'provider_status' => ?string,
 *    'status' => 'sent'|'failed', 'error_code' => ?string, 'error_message' => ?string]
 */
class SmsSendResult
{
    public function __construct(
        public readonly bool $success,
        public readonly ?string $batchReference = null,
        public readonly array $perRecipient = [],
        public readonly ?string $errorCode = null,
        public readonly ?string $errorMessage = null,
        public readonly array $rawResponse = [],
        public readonly ?int $httpStatus = null,
    ) {}
}
