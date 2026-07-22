<?php

namespace App\Services\LegacyMigration\Foundation\Recovery;

final readonly class RecoveryCoordinateHint
{
    public function __construct(
        public int $idempotencyRecordId,
        public int $runId,
        public int $sourceSnapshotId,
        public ?int $targetSnapshotId,
        public ?int $contractBundleId = null,
    ) {
        if ($idempotencyRecordId < 1 || $runId < 1 || $sourceSnapshotId < 1
            || ($targetSnapshotId !== null && $targetSnapshotId < 1)
            || ($contractBundleId !== null && $contractBundleId < 1)) {
            throw RecoveryException::failClosed('RECOVERY-COORDINATE-INVALID');
        }
    }
}
