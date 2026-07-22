<?php

namespace App\Services\LegacyMigration\Foundation\Runtime;

final readonly class MigrationRuntimeAuditSession
{
    public function __construct(
        public int $runId,
        public int $targetSnapshotId,
        public string $runToken,
        public string $targetSnapshotToken,
        public string $contractVersion,
        public string $accessClassification,
        public string $retentionClassification,
    ) {
        if ($runId < 1 || $targetSnapshotId < 1 || $runToken === '' || $targetSnapshotToken === '') {
            throw new RuntimeIsolationException('FOUNDATION_RUNTIME_AUDIT_COORDINATE_INVALID', 'The protected runtime-audit coordinate is invalid.');
        }
    }
}
