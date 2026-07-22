<?php

namespace App\Services\LegacyMigration\Foundation\Snapshot;

use App\Services\LegacyMigration\Foundation\Environment\ConfigurationFingerprintService;
use App\Services\LegacyMigration\Foundation\Environment\EnvironmentGuard;
use App\Services\LegacyMigration\Foundation\Environment\GuardConfiguration;

final readonly class VerifiedCaptureConfiguration
{
    private function __construct(
        public string $environment,
        public GuardConfiguration $guards,
        public string $fingerprint,
        public string $toolVersion,
        public string $accessClassification,
        public string $retentionClassification,
        public string $persistenceAuthorityReference,
    ) {}

    public static function fromRepositoryConfiguration(string $environment, array $configuration): self
    {
        $guards = GuardConfiguration::fromArray($configuration, $environment);
        (new EnvironmentGuard)->assertRuntime($environment, $guards);
        $snapshots = is_array($configuration['snapshots'] ?? null) ? $configuration['snapshots'] : [];
        $execution = is_array($configuration['execution'] ?? null) ? $configuration['execution'] : [];
        if (($snapshots['authoritative_capture_enabled'] ?? false) !== true
            || ($snapshots['physical_target_authority_required'] ?? false) !== true
            || ($snapshots['protected_persistence_required'] ?? false) !== true
            || ($execution['dry_run_only'] ?? false) !== true
            || ($execution['commit_authorized'] ?? true) !== false
            || ($execution['cohort_b_selection_enabled'] ?? true) !== false
            || ($execution['importer_execution_enabled'] ?? true) !== false
            || ($execution['production_enabled'] ?? true) !== false) {
            throw new SnapshotException('FOUNDATION_AUTHORITATIVE_CAPTURE_DISABLED', 'Authoritative capture flags are not fail-closed.');
        }
        $toolVersion = trim((string) ($snapshots['tool_version'] ?? ''));
        $persistenceAuthority = strtolower(trim((string) ($snapshots['persistence_authority_reference'] ?? '')));
        if ($toolVersion === '') {
            throw new SnapshotException('FOUNDATION_AUTHORITATIVE_CAPTURE_VERSION_MISSING', 'Authoritative capture tool version is unavailable.');
        }
        if (preg_match('/\A[a-f0-9]{64}\z/', $persistenceAuthority) !== 1) {
            throw new SnapshotException('FOUNDATION_AUTHORITATIVE_PERSISTENCE_AUTHORITY_MISSING', 'Protected snapshot persistence authority is unavailable.');
        }
        $access = trim((string) (($configuration['protected_store']['access_classification'] ?? null) ?: 'protected'));
        $retention = trim((string) (($configuration['retention']['protected'] ?? null) ?: 'migration-lineage'));
        if ($access === '' || $retention === '' || $retention === 'pending_approved_retention_schedule') {
            throw new SnapshotException('FOUNDATION_AUTHORITATIVE_CAPTURE_CLASSIFICATION_UNRESOLVED', 'Protected capture classifications are unresolved.');
        }

        return new self(
            $environment,
            $guards,
            (new ConfigurationFingerprintService)->fingerprint($guards->fingerprintMaterial($environment)),
            $toolVersion,
            $access,
            $retention,
            $persistenceAuthority,
        );
    }
}
