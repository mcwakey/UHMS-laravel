<?php

namespace App\Services\LegacyMigration\Foundation\Security;

final class LegacyMigrationSecurityFactory
{
    /**
     * @param  array<string, mixed>  $configuration  Exact `legacy-migration` config.
     */
    public static function make(#[\SensitiveParameter] array $configuration, string $environment): HmacTokenService
    {
        $hmac = $configuration['hmac'] ?? null;
        $versions = $configuration['versions'] ?? null;
        if (! is_array($hmac) || ! is_array($versions)
            || ! is_string($versions['canonicalization'] ?? null)
            || ! is_array($hmac['domains'] ?? null)) {
            throw SecurityConfigurationException::forCode('LM-SEC-FOUNDATION-CONFIG-001');
        }

        $canonicalization = new CanonicalizationVersionRegistry(
            [$versions['canonicalization']],
            $versions['canonicalization'],
        );

        return new HmacTokenService(
            new ConfiguredKeyProvider($hmac),
            $canonicalization,
            $environment,
            new TokenDomainRegistry($hmac['domains']),
        );
    }
}
