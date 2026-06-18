<?php

namespace App\Services\Integrations\Providers\Sms;

use App\Contracts\Integrations\SmsProviderInterface;
use App\Services\Integrations\Providers\AbstractIntegrationProvider;
use App\Support\Integrations\ProviderTestResult;
use App\Support\Integrations\Sms\SmsCallbackResult;
use App\Support\Integrations\Sms\SmsSendRequest;
use App\Support\Integrations\Sms\SmsSendResult;
use App\Support\Integrations\Sms\SmsStatusResult;
use Illuminate\Support\Str;

/**
 * In-memory fake SMS provider for local dev, demo, and automated tests.
 * Never performs network I/O. Selectable only when fake providers are allowed
 * (config integrations.allow_fake_providers) — guarded by the resolver.
 */
class FakeSmsProvider extends AbstractIntegrationProvider implements SmsProviderInterface
{
    public function send(SmsSendRequest $request): SmsSendResult
    {
        $perRecipient = [];
        foreach ($request->recipients as $r) {
            $phone = (string) ($r['phone'] ?? '');
            if ($phone === '') {
                continue;
            }
            $perRecipient[$phone] = [
                'provider_message_id' => 'FAKE-' . Str::upper(Str::random(12)),
                'provider_status' => 'ACCEPTED',
                'status' => 'sent',
                'error_code' => null,
                'error_message' => null,
            ];
        }

        return new SmsSendResult(
            success: true,
            batchReference: 'FAKE-BATCH-' . Str::upper(Str::random(8)),
            perRecipient: $perRecipient,
            rawResponse: ['provider' => 'fake_sms', 'accepted' => count($perRecipient)],
            httpStatus: 200,
        );
    }

    public function queryStatus(string $providerMessageId): SmsStatusResult
    {
        return new SmsStatusResult(
            success: true,
            status: 'delivered',
            providerStatus: 'DELIVRD',
            deliveredAt: now()->toIso8601String(),
            raw: ['provider_message_id' => $providerMessageId, 'provider' => 'fake_sms'],
        );
    }

    public function handleCallback(array $payload, array $headers = []): SmsCallbackResult
    {
        return new SmsCallbackResult(
            providerMessageId: $payload['provider_message_id'] ?? ($payload['message_id'] ?? null),
            status: $payload['status'] ?? 'delivered',
            providerStatus: $payload['provider_status'] ?? ($payload['status'] ?? 'DELIVRD'),
            reportedAt: $payload['reported_at'] ?? now()->toIso8601String(),
            eventType: 'delivery_report',
            signatureValid: $this->verifySignature($payload, $headers),
            raw: $payload,
        );
    }

    public function testConnection(): ProviderTestResult
    {
        return ProviderTestResult::pass('Fake SMS provider is reachable (no network).');
    }
}
