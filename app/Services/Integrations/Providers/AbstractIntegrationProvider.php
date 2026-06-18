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
}
