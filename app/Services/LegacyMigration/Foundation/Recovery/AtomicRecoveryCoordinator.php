<?php

namespace App\Services\LegacyMigration\Foundation\Recovery;

final readonly class AtomicRecoveryCoordinator
{
    public function __construct(
        private AtomicRecoveryJournal $journal,
        private CrashBoundaryClassifier $classifier = new CrashBoundaryClassifier,
    ) {}

    public function recover(
        AtomicIntentDescriptor $intent,
        CrashBoundary $boundary,
        RecoveryEvidence $evidence,
        ?RecoveryCheckpoint $checkpointRepair = null,
    ): RecoveryDecision {
        return $this->journal->transaction(function () use ($intent, $boundary, $evidence, $checkpointRepair): RecoveryDecision {
            $snapshot = $this->journal->resolveOrCreateIntent($intent);
            $decision = $this->classifier->classify($boundary, $evidence, $intent->unit);
            if ($decision->unit !== $intent->unit) {
                throw RecoveryException::failClosed('RECOVERY-INTENT-UNIT-MISMATCH');
            }

            if ($decision->disposition === RecoveryDisposition::RepairCheckpointOnly) {
                if ($checkpointRepair === null || $evidence->checkpointPresent) {
                    throw RecoveryException::failClosed('RECOVERY-CHECKPOINT-REPAIR-MISMATCH');
                }
                $this->journal->appendCheckpoint($snapshot, $checkpointRepair);
            } elseif ($checkpointRepair !== null) {
                throw RecoveryException::failClosed('RECOVERY-UNAUTHORIZED-CHECKPOINT-WRITE');
            }

            $this->journal->recordDecision($snapshot, $decision);

            return $decision;
        });
    }
}
