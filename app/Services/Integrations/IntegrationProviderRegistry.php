<?php

namespace App\Services\Integrations;

use App\Contracts\Integrations\PaymentProviderInterface;
use App\Contracts\Integrations\SmsProviderInterface;
use App\Models\IntegrationProvider;
use RuntimeException;

/**
 * Maps a stored provider row to its adapter instance (config/integrations.php
 * `providers` registry) and injects the decrypted credentials. The single place
 * that knows how to build a working adapter from a provider record.
 */
class IntegrationProviderRegistry
{
    public function __construct(protected IntegrationCredentialService $credentials) {}

    public function makeSms(IntegrationProvider $provider): SmsProviderInterface
    {
        $adapter = $this->make($provider, IntegrationProvider::MODULE_SMS);
        if (! $adapter instanceof SmsProviderInterface) {
            throw new RuntimeException("Provider [{$provider->code}] is not an SMS provider.");
        }
        return $adapter;
    }

    public function makePayment(IntegrationProvider $provider): PaymentProviderInterface
    {
        $adapter = $this->make($provider, IntegrationProvider::MODULE_PAYMENT);
        if (! $adapter instanceof PaymentProviderInterface) {
            throw new RuntimeException("Provider [{$provider->code}] is not a payment provider.");
        }
        return $adapter;
    }

    /** Static catalogue of provider codes available for a module type. */
    public function catalogue(string $moduleType): array
    {
        $entries = (array) config("integrations.providers.{$moduleType}", []);
        $allowFake = (bool) config('integrations.allow_fake_providers', false);

        return collect($entries)
            ->reject(fn ($cfg) => ($cfg['is_fake'] ?? false) && ! $allowFake)
            ->map(fn ($cfg, $code) => [
                'code' => $code,
                'label' => $cfg['label'] ?? $code,
                'is_fake' => (bool) ($cfg['is_fake'] ?? false),
                'sandbox_url' => $cfg['sandbox_url'] ?? null,
                'live_url' => $cfg['live_url'] ?? null,
            ])
            ->values()
            ->all();
    }

    protected function make(IntegrationProvider $provider, string $expectedModule): object
    {
        if ($provider->module_type !== $expectedModule) {
            throw new RuntimeException("Provider [{$provider->code}] module mismatch.");
        }

        $config = $provider->registryConfig();
        $class = $config['adapter'] ?? null;
        if (! $class || ! class_exists($class)) {
            throw new RuntimeException("No adapter registered for provider code [{$provider->code}].");
        }

        if (($config['is_fake'] ?? false) && ! config('integrations.allow_fake_providers', false)) {
            throw new RuntimeException('Fake providers are not allowed in this environment.');
        }

        return new $class($provider, $this->credentials->decryptedMap($provider));
    }
}
