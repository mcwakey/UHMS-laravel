<?php

namespace App\Services\LegacyMigration\Foundation\Persistence;

use App\Services\LegacyMigration\Foundation\Validation\ExistingTargetEvidence;

final readonly class PersistenceBoundaryContext
{
    public function __construct(
        public bool $runValid,
        public bool $snapshotsPinned,
        public bool $requiredMappingsResolved,
        public bool $patientStateValidated,
        public bool $patientStateCommitApproved,
        public bool $insuranceInitializationValidated,
        public bool $insuranceInitializationCommitApproved,
        public bool $idempotencyKeyValid,
        public bool $targetCollisionSnapshotCurrent,
        public bool $provenanceAvailable,
        public bool $reconciliationAvailable,
        public ?ExistingTargetEvidence $existingTargetEvidence = null,
        public bool $insuranceHistoryComplete = false,
    ) {}
}
