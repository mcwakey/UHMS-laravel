<?php

namespace App\Contracts\Integrations;

use App\Support\Integrations\ProviderTestResult;
use App\Support\Integrations\Sms\SmsCallbackResult;
use App\Support\Integrations\Sms\SmsSendRequest;
use App\Support\Integrations\Sms\SmsSendResult;
use App\Support\Integrations\Sms\SmsStatusResult;

/**
 * Contract every SMS provider adapter must implement. Controllers/services
 * never talk to a provider directly — they resolve the active provider's
 * adapter and call this interface, so provider-specific behaviour stays
 * isolated inside adapters.
 */
interface SmsProviderInterface
{
    public function code(): string;

    public function send(SmsSendRequest $request): SmsSendResult;

    public function queryStatus(string $providerMessageId): SmsStatusResult;

    public function handleCallback(array $payload, array $headers = []): SmsCallbackResult;

    public function testConnection(): ProviderTestResult;
}
