<?php

namespace App\Services\LegacyMigration\Foundation\Runtime;

use App\Services\LegacyMigration\Foundation\Snapshot\SnapshotManifest;
use App\Services\LegacyMigration\Foundation\Snapshot\SnapshotManifestIntegrityService;
use App\Services\LegacyMigration\Foundation\Snapshot\VerifiedRunPrerequisites;

/** Typed authority minted only after the coordinator verifies sealed run prerequisites and snapshots. */
final readonly class MigrationRunActivationAuthority
{
    private function __construct(
        public string $runToken,
        public string $targetSnapshotId,
        public string $environment,
        public array $approvedEnvironments,
        public ExecutionMode $mode,
        public string $authorityReference,
        private bool $coordinatorVerified,
        private bool $testOnly,
    ) {}

    /** @param list<string> $approvedEnvironments */
    public static function fromAuthoritativeCoordinator(
        VerifiedRunPrerequisites $prerequisites,
        SnapshotManifest $source,
        SnapshotManifest $target,
        SnapshotManifestIntegrityService $integrity,
        string $environment,
        array $approvedEnvironments,
        ExecutionMode $mode,
    ): self {
        foreach ([$source, $target] as $manifest) {
            if (! $integrity->verify($manifest)) {
                throw new RuntimeIsolationException('FOUNDATION_RUNTIME_AUTHORITY_SEAL_INVALID', 'A sealed authoritative snapshot is invalid.');
            }
        }
        if ($source->kind !== 'coordinated_source'
            || $target->kind !== 'target_collision'
            || ! hash_equals($source->runToken, $target->runToken)
            || $prerequisites->references() === []
            || in_array('', $prerequisites->references(), true)) {
            throw new RuntimeIsolationException('FOUNDATION_RUNTIME_COORDINATOR_LINEAGE_INVALID', 'The authoritative runtime coordinate is incomplete or incompatible.');
        }

        return new self(
            $source->runToken,
            $target->snapshotId,
            $environment,
            $approvedEnvironments,
            $mode,
            hash('sha256', json_encode($prerequisites->references(), JSON_THROW_ON_ERROR)),
            true,
            false,
        );
    }

    /** Test-only replacement for the former caller-boolean request surface. */
    /** @param list<string> $approvedEnvironments */
    public static function syntheticForTests(
        string $runToken,
        string $targetSnapshotId,
        ExecutionMode $mode = ExecutionMode::DryRun,
        string $environment = 'testing',
        array $approvedEnvironments = ['testing'],
    ): self {
        if (! defined('PHPUNIT_COMPOSER_INSTALL') && ! defined('__PHPUNIT_PHAR__')) {
            throw new RuntimeIsolationException('FOUNDATION_RUNTIME_SYNTHETIC_AUTHORITY_FORBIDDEN', 'Synthetic runtime authority is restricted to isolated tests.');
        }

        return new self($runToken, $targetSnapshotId, $environment, $approvedEnvironments, $mode, 'synthetic-test-only', true, true);
    }

    public function assertAuthentic(): void
    {
        if (! $this->coordinatorVerified
            || ($this->testOnly && ! defined('PHPUNIT_COMPOSER_INSTALL') && ! defined('__PHPUNIT_PHAR__'))) {
            throw new RuntimeIsolationException('FOUNDATION_RUNTIME_AUTHORITY_INVALID', 'A verified coordinator runtime authority is required.');
        }
    }
}
