<?php

namespace App\Services\LegacyMigration\Foundation\Environment;

final class EnvironmentGuard
{
    public function assertRuntime(string $environment, GuardConfiguration $configuration): void
    {
        if (! $configuration->enabled) {
            throw new FoundationGuardException('FOUNDATION_DISABLED', 'Migration foundation execution is not explicitly enabled.');
        }
        if (! $configuration->rejectProduction
            || ! $configuration->dryRunOnly
            || $configuration->commitAuthorized
            || $configuration->cohortSelectionEnabled
            || $configuration->importerExecutionEnabled
            || $configuration->productionEnabled) {
            throw new FoundationGuardException('FOUNDATION_EXECUTION_POLICY_INVALID', 'Migration foundation execution policy is not safely restricted.');
        }
        $normalized = strtolower(trim($environment));
        if ($normalized === '' || $normalized === 'production' || str_contains($normalized, 'prod')) {
            throw new FoundationGuardException('FOUNDATION_PRODUCTION_FORBIDDEN', 'Migration foundation execution is forbidden in production.');
        }
        if (! in_array($environment, $configuration->approvedEnvironments, true)) {
            throw new FoundationGuardException('FOUNDATION_ENVIRONMENT_NOT_APPROVED', 'The current environment is not explicitly approved for migration foundation execution.');
        }
        if ($configuration->sourceConnection !== GuardConfiguration::SOURCE_CONNECTION
            || $configuration->sourceDatabase !== GuardConfiguration::SOURCE_DATABASE
            || $configuration->sourceVersion !== GuardConfiguration::SOURCE_VERSION
            || $configuration->sourceFingerprint !== GuardConfiguration::SOURCE_FINGERPRINT
            || $configuration->sourceTableCount !== GuardConfiguration::SOURCE_TABLE_COUNT
            || $configuration->sourceColumnCount !== GuardConfiguration::SOURCE_COLUMN_COUNT) {
            throw new FoundationGuardException('FOUNDATION_SOURCE_CONTRACT_INVALID', 'The Classic source guard does not match the approved contract.');
        }
        if (! in_array($configuration->targetConnection, $configuration->targetConnectionAllowList, true)) {
            throw new FoundationGuardException('FOUNDATION_TARGET_CONNECTION_REJECTED', 'The target connection is not explicitly allow-listed.');
        }
        $targetDatabase = strtolower($configuration->targetDatabase);
        if (in_array($targetDatabase, ['uuhms', 'uhms', 'uuhmss'], true)
            || preg_match('/(^|[_-])(prod|production)([_-]|$)/i', $targetDatabase) === 1) {
            throw new FoundationGuardException('FOUNDATION_TARGET_DATABASE_REJECTED', 'The configured target database is forbidden.');
        }
    }

    public function assertSource(GuardConfiguration $configuration, SchemaObservation $observation): void
    {
        $this->assertObservation(
            $observation,
            $configuration->sourceConnection,
            $configuration->sourceDatabase,
            $configuration->sourceVersion,
            $configuration->sourceFingerprint,
            $configuration->sourceTableCount,
            $configuration->sourceColumnCount,
            'SOURCE',
        );
    }

    public function assertTarget(GuardConfiguration $configuration, SchemaObservation $observation): void
    {
        $this->assertObservation(
            $observation,
            $configuration->targetConnection,
            $configuration->targetDatabase,
            $configuration->targetVersion,
            $configuration->targetFingerprint,
            $configuration->targetTableCount,
            $configuration->targetColumnCount,
            'TARGET',
        );
    }

    private function assertObservation(
        SchemaObservation $observation,
        string $connection,
        string $database,
        string $version,
        string $fingerprint,
        int $tables,
        int $columns,
        string $role,
    ): void {
        if (! hash_equals($connection, $observation->connection)) {
            throw new FoundationGuardException("FOUNDATION_{$role}_CONNECTION_MISMATCH", 'A database connection identity did not match its approved coordinate.');
        }
        if (! hash_equals($database, $observation->database)) {
            throw new FoundationGuardException("FOUNDATION_{$role}_DATABASE_MISMATCH", 'A database name did not match its approved coordinate.');
        }
        if (! hash_equals($version, $observation->databaseVersion)) {
            throw new FoundationGuardException("FOUNDATION_{$role}_VERSION_MISMATCH", 'A database version did not match its approved coordinate.');
        }
        if ($tables !== $observation->tableCount || $columns !== $observation->columnCount) {
            throw new FoundationGuardException("FOUNDATION_{$role}_SHAPE_MISMATCH", 'A database table/column shape did not match its approved coordinate.');
        }
        if (! hash_equals($fingerprint, $observation->fingerprint)) {
            throw new FoundationGuardException("FOUNDATION_{$role}_FINGERPRINT_MISMATCH", 'A database structural fingerprint did not match its approved coordinate.');
        }
    }
}
