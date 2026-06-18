<?php

namespace App\Services\Integrations\Providers;

use App\Models\IntegrationProvider;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;

/**
 * Shared behaviour for all provider adapters: holds the provider row + its
 * already-decrypted credentials, and builds a timeout-bounded HTTP client.
 *
 * Credentials are passed in (decrypted by IntegrationCredentialService) so the
 * adapter never touches encryption directly and is trivially unit-testable.
 */
abstract class AbstractIntegrationProvider
{
    /**
     * @param array<string,string|null> $credentials credential_key => plaintext value
     */
    public function __construct(
        protected IntegrationProvider $provider,
        protected array $credentials = [],
    ) {}

    public function code(): string
    {
        return (string) $this->provider->code;
    }

    public function provider(): IntegrationProvider
    {
        return $this->provider;
    }

    protected function credential(string $key, ?string $default = null): ?string
    {
        $value = $this->credentials[$key] ?? $default;
        return ($value === null || $value === '') ? $default : (string) $value;
    }

    protected function hasCredentials(array $required): bool
    {
        foreach ($required as $key) {
            if ($this->credential($key) === null) {
                return false;
            }
        }
        return true;
    }

    protected function baseUrl(): ?string
    {
        return $this->provider->resolveBaseUrl();
    }

    protected function isSandbox(): bool
    {
        return $this->provider->environment !== IntegrationProvider::ENV_LIVE;
    }

    protected function http(): PendingRequest
    {
        return Http::timeout((int) config('integrations.http_timeout', 20))
            ->connectTimeout(10)
            ->acceptJson();
    }

    /* ── Webhook signature/secret verification ──────────────────────── */

    /**
     * Whether an inbound callback is trusted.
     *
     * - If the provider does not require a signature → trusted.
     * - If it requires one: verify the configured signature header against an
     *   HMAC-SHA256 of the payload using the `callback_secret` credential.
     *   (TODO: confirm each provider's exact signing scheme with live creds.)
     * - If verification cannot be performed (missing secret/header/value), only
     *   trust it in sandbox when `allow_unsigned_sandbox_callbacks` is enabled.
     */
    public function verifySignature(array $payload, array $headers = []): bool
    {
        if (! $this->provider->require_signature) {
            return true;
        }

        $secret = $this->credential('callback_secret');
        $headerName = strtolower(trim((string) $this->provider->signature_header));
        $provided = $headerName !== '' ? $this->headerValue($headers, $headerName) : null;

        if ($secret === null || $headerName === '' || $provided === null) {
            return $this->isSandbox() && (bool) $this->provider->allow_unsigned_sandbox_callbacks;
        }

        $expected = hash_hmac('sha256', json_encode($payload), $secret);

        return hash_equals($expected, $provided);
    }

    protected function headerValue(array $headers, string $name): ?string
    {
        foreach ($headers as $key => $value) {
            if (strtolower((string) $key) === $name) {
                return is_array($value) ? (string) ($value[0] ?? '') : (string) $value;
            }
        }
        return null;
    }
}
