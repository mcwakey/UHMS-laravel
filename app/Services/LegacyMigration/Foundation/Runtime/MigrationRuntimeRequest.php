<?php

namespace App\Services\LegacyMigration\Foundation\Runtime;

final readonly class MigrationRuntimeRequest
{
    public function __construct(public MigrationRunActivationAuthority $authority) {}

    public function assertCanActivate(): void
    {
        $this->authority->assertAuthentic();
        $environment = strtolower(trim($this->authority->environment));
        if (PHP_SAPI !== 'cli') {
            throw new RuntimeIsolationException('FOUNDATION_RUNTIME_CONSOLE_REQUIRED', 'Migration isolation may only be activated by an explicit console execution boundary.');
        }
        if ($environment === '' || $environment === 'production' || str_contains($environment, 'prod')) {
            throw new RuntimeIsolationException('FOUNDATION_RUNTIME_PRODUCTION_FORBIDDEN', 'Migration isolation cannot run against production.');
        }
        if (! in_array($this->authority->environment, $this->authority->approvedEnvironments, true)) {
            throw new RuntimeIsolationException('FOUNDATION_RUNTIME_ENVIRONMENT_REJECTED', 'The runtime environment is not explicitly approved.');
        }
        if (! self::isDigest($this->authority->runToken) || ! self::isDigest($this->authority->targetSnapshotId)) {
            throw new RuntimeIsolationException('FOUNDATION_RUNTIME_TOKEN_INVALID', 'A protected runtime coordinate is invalid.');
        }
        if ($this->authority->mode === ExecutionMode::Commit) {
            throw new RuntimeIsolationException(
                'FOUNDATION_RUNTIME_COMMIT_PHASE_BLOCKED',
                'Phase 3 does not authorize a business-domain commit; a later reviewed authority implementation is required.',
            );
        }
    }

    public function runToken(): string
    {
        return $this->authority->runToken;
    }

    public function targetSnapshotId(): string
    {
        return $this->authority->targetSnapshotId;
    }

    public function mode(): ExecutionMode
    {
        return $this->authority->mode;
    }

    private static function isDigest(string $value): bool
    {
        return preg_match('/\A(?:sha256:)?[a-f0-9]{64}\z/i', $value) === 1;
    }
}
