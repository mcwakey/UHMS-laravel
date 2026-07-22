<?php

namespace App\Services\LegacyMigration\Foundation\Recovery;

final class CrashBoundaryClassifier
{
    public function classify(CrashBoundary $boundary, RecoveryEvidence $evidence, ?AtomicUnit $affectedUnit = null): RecoveryDecision
    {
        $unit = $boundary === CrashBoundary::AfterTargetWritesBeforeReconciliation && $affectedUnit !== null
            ? $affectedUnit
            : $this->unit($boundary);

        if (! $evidence->coordinatesMatch) {
            return $this->decision($boundary, RecoveryDisposition::StopAndReclassify, MigrationState::CompensationRequired, $unit, true, false);
        }

        if (! $evidence->lineageCompatible || $evidence->unexpectedDurableFacts) {
            return $this->compensation($boundary, $unit);
        }

        return match ($boundary) {
            CrashBoundary::BeforeNumberAllocation => $this->decision($boundary, RecoveryDisposition::RetryFromClassified, MigrationState::Classified, $unit, false, true),
            CrashBoundary::AfterNumberAllocationBeforePatientCommit => $this->afterAllocation($boundary, $unit, $evidence),
            CrashBoundary::AfterPatientCommitBeforeCrosswalkCommit => $evidence->transactionRolledBack
                ? $this->decision($boundary, RecoveryDisposition::RetryRolledBackUnit, MigrationState::CoreCommitting, $unit, false, true)
                : $this->compensation($boundary, $unit),
            CrashBoundary::AfterCoreCommitBeforeCheckpoint => $this->afterCoreCommit($boundary, $unit, $evidence),
            CrashBoundary::DuringAliasCreation => $this->duringUnit($boundary, $unit, $evidence, RecoveryDisposition::ResumeAliasUnit, MigrationState::AliasPending),
            CrashBoundary::DuringContactCreation => $this->duringUnit($boundary, $unit, $evidence, RecoveryDisposition::ResumeContactUnit, MigrationState::ContactPending),
            CrashBoundary::DuringInsuranceHistory => $this->duringUnit($boundary, $unit, $evidence, RecoveryDisposition::ResumeMissingHistoryRows, MigrationState::InsuranceHistoryPending),
            CrashBoundary::DuringInsuranceCurrent => $this->duringUnit($boundary, $unit, $evidence, RecoveryDisposition::ResumeCurrentGroup, MigrationState::InsuranceCurrentPending),
            CrashBoundary::AfterTargetWritesBeforeReconciliation => $this->afterWrites($boundary, $unit, $evidence),
            CrashBoundary::AfterReconciliationBeforeCompletion => $this->afterReconciliation($boundary, $unit, $evidence),
        };
    }

    private function afterAllocation(CrashBoundary $boundary, AtomicUnit $unit, RecoveryEvidence $evidence): RecoveryDecision
    {
        if (! $evidence->allocationConsumptionExplained) {
            return $this->compensation($boundary, $unit);
        }

        return $this->decision($boundary, RecoveryDisposition::ReuseExactAllocation, MigrationState::CoreCommitting, $unit, false, true);
    }

    private function afterCoreCommit(CrashBoundary $boundary, AtomicUnit $unit, RecoveryEvidence $evidence): RecoveryDecision
    {
        if (! $evidence->durableUnitCommitted || ! $evidence->durableFactsComplete || ! $evidence->mandatoryReconciliationPresent) {
            return $this->compensation($boundary, $unit);
        }
        if (! $evidence->reconciliationPassed) {
            return $this->reconciliationFailed($boundary, $unit);
        }

        return $this->decision($boundary, RecoveryDisposition::RepairCheckpointOnly, MigrationState::CoreCommitted, $unit, false, false);
    }

    private function duringUnit(
        CrashBoundary $boundary,
        AtomicUnit $unit,
        RecoveryEvidence $evidence,
        RecoveryDisposition $resume,
        MigrationState $state,
    ): RecoveryDecision {
        if ($evidence->durableUnitCommitted && ! $evidence->durableFactsComplete) {
            return $this->compensation($boundary, $unit);
        }

        return $this->decision($boundary, $resume, $state, $unit, false, true);
    }

    private function afterWrites(CrashBoundary $boundary, AtomicUnit $unit, RecoveryEvidence $evidence): RecoveryDecision
    {
        if (! $evidence->durableUnitCommitted || ! $evidence->durableFactsComplete) {
            return $this->compensation($boundary, $unit);
        }
        if ($evidence->mandatoryReconciliationPresent && ! $evidence->reconciliationPassed) {
            return $this->reconciliationFailed($boundary, $unit);
        }

        return $this->decision($boundary, RecoveryDisposition::RepairReconciliationOnly, MigrationState::ReconciliationPending, $unit, false, false);
    }

    private function afterReconciliation(CrashBoundary $boundary, AtomicUnit $unit, RecoveryEvidence $evidence): RecoveryDecision
    {
        if (! $evidence->durableUnitCommitted || ! $evidence->durableFactsComplete || ! $evidence->mandatoryReconciliationPresent || ! $evidence->reconciliationPassed) {
            return $this->reconciliationFailed($boundary, $unit);
        }

        return $this->decision($boundary, RecoveryDisposition::RepairCompletionOnly, MigrationState::ReconciliationPassed, $unit, false, false);
    }

    private function compensation(CrashBoundary $boundary, AtomicUnit $unit): RecoveryDecision
    {
        return $this->decision($boundary, RecoveryDisposition::CompensationRequired, MigrationState::CompensationRequired, $unit, true, false);
    }

    private function reconciliationFailed(CrashBoundary $boundary, AtomicUnit $unit): RecoveryDecision
    {
        return $this->decision($boundary, RecoveryDisposition::ReconciliationFailed, MigrationState::ReconciliationFailed, $unit, true, false);
    }

    private function decision(CrashBoundary $boundary, RecoveryDisposition $disposition, MigrationState $state, AtomicUnit $unit, bool $review, bool $writes): RecoveryDecision
    {
        return new RecoveryDecision($boundary, $disposition, $state, $unit, $review, $writes);
    }

    private function unit(CrashBoundary $boundary): AtomicUnit
    {
        return match ($boundary) {
            CrashBoundary::BeforeNumberAllocation,
            CrashBoundary::AfterNumberAllocationBeforePatientCommit,
            CrashBoundary::AfterPatientCommitBeforeCrosswalkCommit,
            CrashBoundary::AfterCoreCommitBeforeCheckpoint,
            CrashBoundary::AfterReconciliationBeforeCompletion => AtomicUnit::PatientCore,
            CrashBoundary::DuringAliasCreation => AtomicUnit::OpdAlias,
            CrashBoundary::DuringContactCreation => AtomicUnit::EmergencyContact,
            CrashBoundary::DuringInsuranceHistory => AtomicUnit::InsuranceHistory,
            CrashBoundary::DuringInsuranceCurrent => AtomicUnit::InsuranceCurrent,
            CrashBoundary::AfterTargetWritesBeforeReconciliation => AtomicUnit::PatientCore,
        };
    }
}
