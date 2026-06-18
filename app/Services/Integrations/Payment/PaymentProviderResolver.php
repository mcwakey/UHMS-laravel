<?php

namespace App\Services\Integrations\Payment;

use App\Contracts\Integrations\PaymentProviderInterface;
use App\Exceptions\Integrations\IntegrationException;
use App\Models\IntegrationProvider;
use App\Services\Integrations\IntegrationProviderRegistry;
use App\Services\Integrations\IntegrationProviderService;

/**
 * Resolves the single active payment provider and builds its adapter.
 */
class PaymentProviderResolver
{
    public function __construct(
        protected IntegrationProviderService $providers,
        protected IntegrationProviderRegistry $registry,
    ) {}

    public function activeProvider(): ?IntegrationProvider
    {
        return $this->providers->activeProvider(IntegrationProvider::MODULE_PAYMENT);
    }

    public function requireActiveProvider(): IntegrationProvider
    {
        return $this->activeProvider() ?: throw IntegrationException::notConfigured('payment');
    }

    public function adapterFor(IntegrationProvider $provider): PaymentProviderInterface
    {
        return $this->registry->makePayment($provider);
    }

    /** Resolve a provider by its code (used by inbound callbacks). */
    public function findByCode(string $code): ?IntegrationProvider
    {
        return IntegrationProvider::query()
            ->payment()
            ->where('code', $code)
            ->first();
    }
}
