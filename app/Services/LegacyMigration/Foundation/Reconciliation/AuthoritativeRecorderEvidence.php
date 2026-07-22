<?php

namespace App\Services\LegacyMigration\Foundation\Reconciliation;

final readonly class AuthoritativeRecorderEvidence
{
    /** @param array<string,mixed> $observations */
    private function __construct(
        public string $runToken,
        public string $sourceSnapshotId,
        public string $targetBeforeSnapshotId,
        public string $targetAfterSnapshotId,
        public array $observations,
        public string $authorityReference,
    ) {}

    public static function issueFromSession(AuthoritativeRecorderSession $session): self
    {
        $material = $session->releaseEvidenceMaterial();

        return new self(
            $material['run_token'],
            $material['source_snapshot_id'],
            $material['target_before_snapshot_id'],
            $material['target_after_snapshot_id'],
            $material['observations'],
            $material['authority_reference'],
        );
    }
}
