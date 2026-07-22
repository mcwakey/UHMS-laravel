<?php

namespace App\Services\LegacyMigration\Foundation\Runtime;

final readonly class MigrationRuntimeRequest
{
    /** @param array<int, string> $approvedEnvironments */
    public function __construct(
        public string $runToken,
        public string $targetSnapshotId,
        public string $environment,
        public array $approvedEnvironments,
        public ExecutionMode $mode,
        public bool $foundationEnabled,
        public bool $initiatedFromConsole,
        public bool $productionTarget,
        public bool $runValid,
        public bool $sourceSnapshotPinned,
        public bool $targetSnapshotPinned,
        public bool $targetCollisionSnapshotCurrent,
        public bool $dryRunOnly = true,
        public bool $commitAuthorized = false,
    ) {}

    public function assertCanActivate(): void
    {
        $environment = strtolower(trim($this->environment));
        if (! $this->foundationEnabled) {
            throw new RuntimeIsolationException('FOUNDATION_RUNTIME_DISABLED', 'The migration runtime is disabled.');
        }
        if (! $this->initiatedFromConsole) {
            throw new RuntimeIsolationException('FOUNDATION_RUNTIME_CONSOLE_REQUIRED', 'Migration isolation may only be activated by an explicit console execution boundary.');
        }
        if ($environment === '' || $environment === 'production' || str_contains($environment, 'prod') || $this->productionTarget) {
            throw new RuntimeIsolationException('FOUNDATION_RUNTIME_PRODUCTION_FORBIDDEN', 'Migration isolation cannot run against production.');
        }
        if (! in_array($this->environment, $this->approvedEnvironments, true)) {
            throw new RuntimeIsolationException('FOUNDATION_RUNTIME_ENVIRONMENT_REJECTED', 'The runtime environment is not explicitly approved.');
        }
        if (! $this->runValid || ! $this->sourceSnapshotPinned || ! $this->targetSnapshotPinned || ! $this->targetCollisionSnapshotCurrent) {
            throw new RuntimeIsolationException('FOUNDATION_RUNTIME_COORDINATE_INVALID', 'A valid run and current pinned snapshots are required.');
        }
        if (! self::isDigest($this->runToken) || ! self::isDigest($this->targetSnapshotId)) {
            throw new RuntimeIsolationException('FOUNDATION_RUNTIME_TOKEN_INVALID', 'A protected runtime coordinate is invalid.');
        }
        if ($this->mode === ExecutionMode::Commit) {
            throw new RuntimeIsolationException(
                'FOUNDATION_RUNTIME_COMMIT_PHASE_BLOCKED',
                'Phase 3 does not authorize a business-domain commit; a later reviewed authority implementation is required.',
            );
        }
    }

    private static function isDigest(string $value): bool
    {
        return preg_match('/\A(?:sha256:)?[a-f0-9]{64}\z/i', $value) === 1;
    }
}
