<?php

namespace App\Services\LegacyMigration\Foundation\Snapshot;

final readonly class AuthoritativeRunCaptureResult
{
    /** @param array<string,int> $collisionRecordIds */
    public function __construct(
        public int $contractBundleRecordId,
        public int $runRecordId,
        public int $sourceSnapshotRecordId,
        public int $targetSnapshotRecordId,
        public array $collisionRecordIds,
        public RunManifest $runManifest,
        public SnapshotManifest $sourceSnapshotManifest,
        public SnapshotManifest $targetSnapshotManifest,
    ) {}
}
