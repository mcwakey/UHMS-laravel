<?php

namespace App\Services\LegacyMigration\Foundation\Reconciliation;

final readonly class AuthoritativeDryRunResult
{
    /** @param array<string,array<string,int|float|string|bool|null>> $measurements */
    public function __construct(
        public string $verdict,
        public string $bundleHash,
        public string $runToken,
        public string $sourceSnapshotId,
        public string $targetSnapshotId,
        public array $measurements,
        public array $missing,
        public array $failed,
    ) {}

    public function commitAuthorized(): bool
    {
        return false;
    }
}
