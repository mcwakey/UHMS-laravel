<?php

namespace App\Services\Integrations\Sms;

use App\Models\IntegrationProvider;
use App\Models\SmsProviderCallback;

/**
 * Stores and processes inbound SMS provider callbacks (delivery reports).
 * Every callback is stored; processing delegates to the gateway/delivery-report
 * services which are idempotent.
 */
class SmsCallbackService
{
    public function __construct(protected SmsGatewayService $gateway) {}

    public function store(string $providerCode, array $payload, array $headers = [], ?string $ip = null): SmsProviderCallback
    {
        $provider = IntegrationProvider::query()->sms()->where('code', $providerCode)->first();

        return SmsProviderCallback::create([
            'provider_id' => $provider?->id,
            'provider_code' => $providerCode,
            'event_type' => 'delivery_report',
            'provider_message_id' => $payload['message_id'] ?? ($payload['provider_message_id'] ?? null),
            'raw_payload' => $payload,
            'headers_snapshot' => $this->safeHeaders($headers),
            'ip_address' => $ip,
        ]);
    }

    public function process(SmsProviderCallback $callback): SmsProviderCallback
    {
        $provider = IntegrationProvider::query()->sms()->where('code', $callback->provider_code)->first();
        if (! $provider) {
            $callback->update(['processed' => true, 'processed_at' => now(), 'processing_error' => 'unknown_provider']);
            return $callback;
        }

        try {
            $result = $this->gateway->handleDeliveryCallback($provider, (array) $callback->raw_payload, (array) $callback->headers_snapshot);
            $callback->update([
                'signature_valid' => $result->signatureValid,
                'provider_message_id' => $result->providerMessageId ?: $callback->provider_message_id,
                'processed' => true,
                'processed_at' => now(),
            ]);
        } catch (\Throwable $e) {
            $callback->update(['processed' => true, 'processed_at' => now(), 'processing_error' => 'processing_error']);
        }

        return $callback;
    }

    public function handle(string $providerCode, array $payload, array $headers = [], ?string $ip = null): SmsProviderCallback
    {
        return $this->process($this->store($providerCode, $payload, $headers, $ip));
    }

    private function safeHeaders(array $headers): array
    {
        $drop = ['authorization', 'cookie', 'x-api-key'];
        $out = [];
        foreach ($headers as $key => $value) {
            $out[$key] = in_array(strtolower((string) $key), $drop, true) ? '***MASKED***' : $value;
        }
        return $out;
    }
}
