<?php

namespace App\Services\LegacyMigration\Foundation\Environment;

use Throwable;

final class FoundationPreflightService
{
    public function __construct(
        private readonly EnvironmentGuard $environmentGuard = new EnvironmentGuard,
        private readonly SchemaFingerprintService $fingerprints = new SchemaFingerprintService,
        private readonly SourceAccountVerifier $sourceAccountVerifier = new SourceAccountVerifier,
        private readonly ConfigurationFingerprintService $configurationFingerprints = new ConfigurationFingerprintService,
    ) {}

    public function run(
        string $environment,
        GuardConfiguration $configuration,
        MetadataConnection $source,
        MetadataConnection $target,
    ): FoundationPreflightResult {
        $this->environmentGuard->assertRuntime($environment, $configuration);
        $this->assertConfiguredCoordinate($source, $configuration->sourceConnection, $configuration->sourceDatabase, 'SOURCE');
        $this->assertConfiguredCoordinate($target, $configuration->targetConnection, $configuration->targetDatabase, 'TARGET');

        $source->beginReadOnlySnapshot();
        try {
            $account = $this->sourceAccountVerifier->verify($source);
            $sourceObservation = $this->fingerprints->inspectSource($source, $configuration->sourceDatabase);
            $this->environmentGuard->assertSource($configuration, $sourceObservation);
        } catch (Throwable $exception) {
            $source->rollbackReadOnlySnapshot();
            throw $exception;
        }

        try {
            $target->beginReadOnlySnapshot();
            try {
                $targetObservation = $this->fingerprints->inspectTarget($target, $configuration->targetDatabase);
                $this->environmentGuard->assertTarget($configuration, $targetObservation);
            } finally {
                $target->rollbackReadOnlySnapshot();
            }
        } finally {
            $source->rollbackReadOnlySnapshot();
        }

        return new FoundationPreflightResult(
            $sourceObservation,
            $targetObservation,
            $account,
            $this->configurationFingerprints->fingerprint($configuration->fingerprintMaterial($environment)),
        );
    }

    private function assertConfiguredCoordinate(MetadataConnection $connection, string $name, string $database, string $role): void
    {
        if (! hash_equals($name, $connection->name()) || ! hash_equals($database, $connection->configuredDatabase())) {
            throw new FoundationGuardException("FOUNDATION_{$role}_CONFIGURATION_MISMATCH", 'A configured database coordinate does not match its approved guard.');
        }
    }
}
