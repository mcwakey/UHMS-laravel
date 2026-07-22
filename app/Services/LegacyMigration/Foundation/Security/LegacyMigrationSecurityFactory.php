<?php

namespace App\Services\LegacyMigration\Foundation\Security;

final class LegacyMigrationSecurityFactory
{
    /**
     * @param  array<string, mixed>  $configuration  Exact `legacy-migration` config.
     */
    public static function make(#[\SensitiveParameter] array $configuration, string $environment, ?callable $externalSecretResolver = null): HmacTokenService
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
            self::keyProvider($configuration, $hmac, $environment, $externalSecretResolver),
            $canonicalization,
            $environment,
            new TokenDomainRegistry($hmac['domains']),
        );
    }

    /** @param array<string, mixed> $configuration @param array<string, mixed> $legacyHmac */
    private static function keyProvider(array $configuration, array $legacyHmac, string $environment, ?callable $resolver): KeyProvider
    {
        $configured = $configuration['key_provider'] ?? null;
        if (! is_array($configured) || ($configured['provider'] ?? null) !== 'external_reference') {
            // Compatibility path for existing isolated Phase 3 tests. Deployed
            // Phase 3B configuration must select external_reference explicitly.
            return new ConfiguredKeyProvider($legacyHmac);
        }
        $references = $configured['references'] ?? null;
        if (! is_array($references) || $references === []) {
            $manifest = $configured['reference_manifest'] ?? null;
            $manifestHash = $configured['reference_manifest_hash'] ?? null;
            if (! is_string($manifest) || ! is_string($manifestHash)) {
                throw SecurityConfigurationException::forCode('LM-SEC-KEY-REFERENCE-LIST-001');
            }
            $references = (new KeyReferenceManifestLoader)->load($manifest, $manifestHash);
        }
        $parsed = [];
        foreach ($references as $reference) {
            if (! is_array($reference)) {
                throw SecurityConfigurationException::forCode('LM-SEC-KEY-REFERENCE-LIST-001');
            }
            try {
                $parsed[] = new OperationalKeyReference(
                    (string) ($reference['key_id'] ?? ''),
                    (string) ($reference['version'] ?? ''),
                    (string) ($reference['secret_reference'] ?? ''),
                    (string) ($reference['environment'] ?? ''),
                    is_array($reference['domains'] ?? null) ? array_values($reference['domains']) : [],
                    new \DateTimeImmutable((string) ($reference['activated_at'] ?? '')),
                    isset($reference['retired_at']) ? new \DateTimeImmutable((string) $reference['retired_at']) : null,
                    isset($reference['verification_expires_at']) ? new \DateTimeImmutable((string) $reference['verification_expires_at']) : null,
                    (string) ($reference['rotation_authority'] ?? ''),
                    ($reference['revoked'] ?? true) === true,
                    ($reference['active_for_signing'] ?? false) === true,
                );
            } catch (\Throwable $exception) {
                if ($exception instanceof SecurityConfigurationException) {
                    throw $exception;
                }
                throw SecurityConfigurationException::forCode('LM-SEC-KEY-REFERENCE-METADATA-001');
            }
        }
        $resolver ??= static function (string $reference): ?string {
            if (preg_match('#\Aenv://([A-Z][A-Z0-9_]{2,127})\z#D', $reference, $matches) !== 1) {
                throw SecurityConfigurationException::forCode('LM-SEC-KEY-RESOLVER-NOT-BOUND-001');
            }
            $value = getenv($matches[1]);

            return is_string($value) ? $value : null;
        };
        $defaultDomain = $parsed[0]->domains[0] ?? throw SecurityConfigurationException::forCode('LM-SEC-KEY-REFERENCE-METADATA-001');

        return new ExternalReferenceKeyProvider($parsed, $environment, $defaultDomain, $resolver);
    }
}
