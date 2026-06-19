<?php

namespace App\Services\Integrations\Providers\Sms;

use App\Contracts\Integrations\SmsProviderInterface;
use App\Services\Integrations\Providers\AbstractIntegrationProvider;
use App\Support\Integrations\ProviderTestResult;
use App\Support\Integrations\Sms\SmsCallbackResult;
use App\Support\Integrations\Sms\SmsSendRequest;
use App\Support\Integrations\Sms\SmsSendResult;
use App\Support\Integrations\Sms\SmsStatusResult;
use Illuminate\Support\Facades\Log;

/**
 * Nalo Solutions SMS adapter.
 *
 * Credentials (integration_provider_credentials): api_key, username, password,
 * sender_id, client_id, client_secret. Nalo accepts either api_key auth or
 * username/password auth depending on the account.
 *
 * NOTE (Phase 1): The exact request/response contract must be confirmed against
 * the live Nalo account before enabling in production — the payload below
 * follows Nalo's documented clientapi shape. Where the contract is uncertain it
 * is marked TODO. This adapter NEVER fakes a successful send: any transport or
 * mapping error returns a failed SmsSendResult so the failure is retained.
 */
class NaloSmsProvider extends AbstractIntegrationProvider implements SmsProviderInterface
{
    public function send(SmsSendRequest $request): SmsSendResult
    {
        $url = $this->jsonSendUrl();
        if (! $url) {
            return $this->failAll($request, 'no_base_url', 'Nalo SMS base URL is not configured.');
        }
        if (! $this->authKey() && ! $this->hasCredentials(['username', 'password'])) {
            return $this->failAll($request, 'no_credentials', 'Nalo SMS credentials are not configured.');
        }

        $sender = $request->senderId ?: $this->credential('sender_id', $this->provider->sender_id);

        $perRecipient = [];
        $rawResponses = [];
        $anyOk = false;

        // Nalo's clientapi sends per-destination; loop keeps per-recipient status.
        foreach ($request->recipients as $r) {
            $phone = (string) ($r['phone'] ?? '');
            if ($phone === '') {
                continue;
            }

            $payload = $this->authKey()
                ? [
                    'key' => $this->authKey(),
                    'msisdn' => $phone,
                    'message' => $request->body,
                    'sender_id' => $sender,
                ]
                : [
                    'username' => $this->credential('username'),
                    'password' => $this->credential('password'),
                    'msisdn' => $phone,
                    'message' => $request->body,
                    'sender_id' => $sender,
                ];

            $payload = array_filter($payload, fn ($v) => $v !== null && $v !== '');

            try {
                $response = $this->http()->asJson()->post($url, $payload);
                $body = $this->decodeResponse($response->body());
                $ok = $response->successful() && $this->looksAccepted($body);
                $rawResponses[$phone] = $body;

                $perRecipient[$phone] = [
                    'provider_message_id' => $body['job_id'] ?? ($body['message_id'] ?? null),
                    'provider_status' => (string) ($body['status'] ?? $response->status()),
                    'status' => $ok ? 'sent' : 'failed',
                    'error_code' => $ok ? null : (string) ($body['code'] ?? $body['status'] ?? $response->status()),
                    'error_message' => $ok ? null : $this->errorMessage($body),
                ];
                $anyOk = $anyOk || $ok;
            } catch (\Throwable $e) {
                Log::warning('NaloSmsProvider send failed', ['error' => $e->getMessage()]);
                $perRecipient[$phone] = [
                    'provider_message_id' => null,
                    'provider_status' => null,
                    'status' => 'failed',
                    'error_code' => 'transport_error',
                    'error_message' => 'Could not reach the SMS provider.',
                ];
            }
        }

        return new SmsSendResult(
            success: $anyOk,
            batchReference: $request->reference,
            perRecipient: $perRecipient,
            errorCode: $anyOk ? null : 'send_failed',
            errorMessage: $anyOk ? null : 'No messages were accepted by the provider.',
            rawResponse: $rawResponses,
        );
    }

    public function queryStatus(string $providerMessageId): SmsStatusResult
    {
        // TODO: implement Nalo delivery-status query endpoint when available.
        return new SmsStatusResult(
            success: false,
            status: null,
            errorMessage: 'Status query is not implemented for Nalo SMS yet.',
        );
    }

    public function handleCallback(array $payload, array $headers = []): SmsCallbackResult
    {
        // Nalo posts delivery reports back to the configured callback URL.
        // TODO: confirm field names + signature scheme with the live account.
        $status = strtolower((string) ($payload['status'] ?? $payload['dlr_status'] ?? ''));
        $normalized = match (true) {
            str_contains($status, 'deliv') => 'delivered',
            str_contains($status, 'fail'), str_contains($status, 'undeliv'), str_contains($status, 'expir') => 'undelivered',
            default => 'sent',
        };

        return new SmsCallbackResult(
            providerMessageId: $payload['message_id'] ?? ($payload['job_id'] ?? null),
            status: $normalized,
            providerStatus: $payload['status'] ?? ($payload['dlr_status'] ?? null),
            reportedAt: $payload['timestamp'] ?? now()->toIso8601String(),
            eventType: 'delivery_report',
            signatureValid: $this->verifySignature($payload, $headers),
            raw: $payload,
        );
    }

    public function testConnection(): ProviderTestResult
    {
        if (! $this->baseUrl()) {
            return ProviderTestResult::fail('Base URL is not configured.');
        }
        if (! $this->authKey() && ! $this->hasCredentials(['username', 'password'])) {
            return ProviderTestResult::fail('Credentials are not configured (auth_key/api_key OR username+password).');
        }

        return ProviderTestResult::pass('Configuration present. Nalo POST JSON send is ready for a manual test SMS.');
    }

    /* ── helpers ────────────────────────────────────────────────────── */

    private function failAll(SmsSendRequest $request, string $code, string $message): SmsSendResult
    {
        $perRecipient = [];
        foreach ($request->phones() as $phone) {
            $perRecipient[$phone] = [
                'provider_message_id' => null,
                'provider_status' => null,
                'status' => 'failed',
                'error_code' => $code,
                'error_message' => $message,
            ];
        }
        return new SmsSendResult(false, $request->reference, $perRecipient, $code, $message);
    }

    private function authKey(): ?string
    {
        return $this->credential('auth_key') ?: $this->credential('api_key');
    }

    private function jsonSendUrl(): ?string
    {
        $url = $this->baseUrl();
        if (! $url) {
            return null;
        }

        return str_replace('/smsbackend/clientapi/Resl_Nalo/', '/smsbackend/Resl_Nalo/', $url);
    }

    private function decodeResponse(string $body): array
    {
        $decoded = json_decode($body, true);
        if (is_array($decoded)) {
            return $decoded;
        }

        if (preg_match('/^(?<status>\d+)\|(?<msisdn>[^|]+)\|(?<job_id>.+)$/', trim($body), $matches)) {
            return [
                'status' => $matches['status'],
                'msisdn' => $matches['msisdn'],
                'job_id' => $matches['job_id'],
                'raw' => $body,
            ];
        }

        return ['raw' => $body];
    }

    private function looksAccepted(array $body): bool
    {
        $status = strtolower((string) ($body['status'] ?? ''));
        return in_array($status, ['1701', 'success', 'accepted', 'ok', 'sent'], true)
            || (isset($body['job_id']) && $body['job_id'] !== '')
            || (isset($body['message_id']) && $body['message_id'] !== '');
    }

    private function errorMessage(array $body): string
    {
        if (isset($body['message']) && $body['message'] !== '') {
            return (string) $body['message'];
        }

        return match ((string) ($body['status'] ?? $body['code'] ?? '')) {
            '1702' => 'Invalid URL or missing required parameter.',
            '1703' => 'Invalid username or password.',
            '1704' => 'Invalid message type.',
            '1705' => 'Invalid message.',
            '1706' => 'Invalid destination.',
            '1707' => 'Invalid source/sender.',
            '1708' => 'Invalid DLR value.',
            '1709' => 'User validation failed.',
            '1710' => 'Nalo internal error.',
            '1025' => 'Insufficient user credit.',
            '1026' => 'Insufficient reseller credit.',
            default => 'Provider rejected the message.',
        };
    }
}
