<?php

namespace App\Support\Integrations\Sms;

/**
 * Normalised SMS send request handed to a provider adapter.
 *
 * @param array<int,array{recipient_id:?int,phone:string,name:?string}> $recipients
 */
class SmsSendRequest
{
    public function __construct(
        public readonly string $body,
        public readonly array $recipients,
        public readonly ?string $senderId = null,
        public readonly ?string $reference = null,
        public readonly string $messageType = 'manual',
        public readonly array $metadata = [],
    ) {}

    /** @return array<int,string> normalised phone numbers only */
    public function phones(): array
    {
        return array_values(array_map(fn ($r) => (string) ($r['phone'] ?? ''), $this->recipients));
    }
}
