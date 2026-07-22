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
    ): RecoveryDecision {
        return $this->journal->transaction(function () use ($intent, $boundary): RecoveryDecision {
            $snapshot = $this->journal->resolveOrCreateIntent($intent);
            $observation = $this->journal->observe($snapshot, $boundary);
            $replay = $this->journal->replayDecision($snapshot, $boundary, $observation);
            if ($replay !== null) {
                return $replay;
            }
            if ($observation->boundary !== $boundary) {
                throw RecoveryException::failClosed('RECOVERY-OBSERVATION-BOUNDARY-MISMATCH');
            }
            $evidence = $observation->evidence;
            $decision = $this->classifier->classify($boundary, $evidence, $intent->unit);
            if ($decision->unit !== $intent->unit) {
                throw RecoveryException::failClosed('RECOVERY-INTENT-UNIT-MISMATCH');
            }

            if ($decision->disposition === RecoveryDisposition::RepairCheckpointOnly) {
                if ($observation->checkpointRepair === null || $evidence->checkpointPresent) {
                    throw RecoveryException::failClosed('RECOVERY-CHECKPOINT-REPAIR-MISMATCH');
                }
                $this->journal->appendCheckpoint($snapshot, $observation->checkpointRepair);
                $recordObservation = $this->journal->observe($snapshot, $boundary);
                if (! $recordObservation->evidence->checkpointPresent || $recordObservation->checkpointRepair !== null) {
                    throw RecoveryException::failClosed('RECOVERY-CHECKPOINT-REPAIR-UNVERIFIED');
                }
            } elseif ($observation->checkpointRepair !== null) {
                throw RecoveryException::failClosed('RECOVERY-UNAUTHORIZED-CHECKPOINT-WRITE');
            }

            $this->journal->recordDecision($snapshot, $decision, $recordObservation ?? $observation);

            return $decision;
        });
    }
}
