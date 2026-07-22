<?php

namespace Tests\Unit\LegacyMigration\Foundation\Recovery;

use App\Services\LegacyMigration\Foundation\Recovery\AtomicIntentDescriptor;
use App\Services\LegacyMigration\Foundation\Recovery\AtomicIntentSnapshot;
use App\Services\LegacyMigration\Foundation\Recovery\AtomicRecoveryCoordinator;
use App\Services\LegacyMigration\Foundation\Recovery\AtomicRecoveryJournal;
use App\Services\LegacyMigration\Foundation\Recovery\AtomicUnit;
use App\Services\LegacyMigration\Foundation\Recovery\CompareAndSetStateStore;
use App\Services\LegacyMigration\Foundation\Recovery\CompensationPlanRegistry;
use App\Services\LegacyMigration\Foundation\Recovery\CrashBoundary;
use App\Services\LegacyMigration\Foundation\Recovery\CrashBoundaryClassifier;
use App\Services\LegacyMigration\Foundation\Recovery\MigrationState;
use App\Services\LegacyMigration\Foundation\Recovery\MonotonicStateMachine;
use App\Services\LegacyMigration\Foundation\Recovery\RecoveryCheckpoint;
use App\Services\LegacyMigration\Foundation\Recovery\RecoveryDecision;
use App\Services\LegacyMigration\Foundation\Recovery\RecoveryDisposition;
use App\Services\LegacyMigration\Foundation\Recovery\RecoveryEvidence;
use App\Services\LegacyMigration\Foundation\Recovery\RecoveryException;
use App\Services\LegacyMigration\Foundation\Recovery\StateSnapshot;
use Closure;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class RecoveryFoundationTest extends TestCase
{
    #[Test]
    public function state_machine_contains_the_exact_24_phase_2f_states(): void
    {
        self::assertSame([
            'NOT_STARTED', 'EXTRACTED', 'CLASSIFIED', 'DRY_RUN_ACCEPTED', 'CORE_COMMITTING', 'CORE_COMMITTED',
            'ALIAS_PENDING', 'ALIAS_COMMITTED', 'ALIAS_WITHHELD', 'CONTACT_PENDING', 'CONTACT_COMMITTED',
            'CONTACT_WITHHELD', 'INSURANCE_HISTORY_PENDING', 'INSURANCE_HISTORY_COMMITTED',
            'INSURANCE_CURRENT_PENDING', 'INSURANCE_CURRENT_COMMITTED', 'INSURANCE_CURRENT_WITHHELD',
            'RECONCILIATION_PENDING', 'RECONCILIATION_PASSED', 'RECONCILIATION_FAILED', 'QUARANTINED',
            'ROLLBACK_REQUIRED', 'COMPENSATION_REQUIRED', 'COMPLETED',
        ], MonotonicStateMachine::states());
    }

    #[Test]
    public function compare_and_set_enforces_expected_prior_state_version_and_attempt(): void
    {
        $store = new InMemoryCasStore(new StateSnapshot('synthetic', MigrationState::NotStarted, 0, 0));
        $machine = new MonotonicStateMachine($store);

        $next = $machine->transition($store->snapshot, MigrationState::Extracted);
        self::assertSame(1, $next->version);
        self::assertSame(1, $next->attemptCount);

        $this->expectException(RecoveryException::class);
        $machine->transition(new StateSnapshot('synthetic', MigrationState::NotStarted, 0, 0), MigrationState::Extracted);
    }

    #[Test]
    public function terminal_and_invalid_transitions_fail_closed(): void
    {
        $machine = new MonotonicStateMachine(new InMemoryCasStore(new StateSnapshot('synthetic', MigrationState::Completed, 9, 9)));

        $this->expectException(RecoveryException::class);
        $machine->transition(new StateSnapshot('synthetic', MigrationState::Completed, 9, 9), MigrationState::CoreCommitting);
    }

    #[Test]
    #[DataProvider('crashBoundaries')]
    public function each_phase_2f_crash_boundary_has_an_explicit_safe_recovery(
        CrashBoundary $boundary,
        RecoveryEvidence $evidence,
        RecoveryDisposition $expected,
    ): void {
        $decision = (new CrashBoundaryClassifier)->classify($boundary, $evidence);

        self::assertSame($expected, $decision->disposition);
    }

    /** @return iterable<string, array{CrashBoundary, RecoveryEvidence, RecoveryDisposition}> */
    public static function crashBoundaries(): iterable
    {
        $clean = RecoveryEvidence::cleanRetry();
        $committed = new RecoveryEvidence(true, true, false, true, true, false, true, true, false, true);

        yield 'PILOT-RESUME-001 before allocation' => [CrashBoundary::BeforeNumberAllocation, $clean, RecoveryDisposition::RetryFromClassified];
        yield 'PILOT-RESUME-002 allocation lag' => [CrashBoundary::AfterNumberAllocationBeforePatientCommit, $clean, RecoveryDisposition::ReuseExactAllocation];
        yield 'PILOT-RESUME-003 atomic rollback' => [CrashBoundary::AfterPatientCommitBeforeCrosswalkCommit, new RecoveryEvidence(true, true, false, false, false, false, false, false, true, true), RecoveryDisposition::RetryRolledBackUnit];
        yield 'PILOT-RESUME-004 checkpoint lag' => [CrashBoundary::AfterCoreCommitBeforeCheckpoint, $committed, RecoveryDisposition::RepairCheckpointOnly];
        yield 'PILOT-RESUME-005 alias' => [CrashBoundary::DuringAliasCreation, $clean, RecoveryDisposition::ResumeAliasUnit];
        yield 'PILOT-RESUME-006 contact' => [CrashBoundary::DuringContactCreation, $clean, RecoveryDisposition::ResumeContactUnit];
        yield 'PILOT-RESUME-007 history' => [CrashBoundary::DuringInsuranceHistory, $clean, RecoveryDisposition::ResumeMissingHistoryRows];
        yield 'PILOT-RESUME-008 current' => [CrashBoundary::DuringInsuranceCurrent, $clean, RecoveryDisposition::ResumeCurrentGroup];
        yield 'PILOT-RESUME-009 reconciliation lag' => [CrashBoundary::AfterTargetWritesBeforeReconciliation, new RecoveryEvidence(true, true, false, true, true, false, false, false, false, true), RecoveryDisposition::RepairReconciliationOnly];
        yield 'PILOT-RESUME-010 terminal lag' => [CrashBoundary::AfterReconciliationBeforeCompletion, $committed, RecoveryDisposition::RepairCompletionOnly];
    }

    #[Test]
    public function partial_durable_unit_is_never_treated_as_success(): void
    {
        $evidence = new RecoveryEvidence(true, true, false, true, false, false, false, false, false, true);
        $decision = (new CrashBoundaryClassifier)->classify(CrashBoundary::DuringContactCreation, $evidence);

        self::assertSame(RecoveryDisposition::CompensationRequired, $decision->disposition);
        self::assertTrue($decision->operatorReviewRequired);
        self::assertFalse($decision->targetWritesPermitted);
    }

    #[Test]
    public function contradictory_recovery_evidence_is_rejected_before_classification(): void
    {
        $this->expectException(RecoveryException::class);
        new RecoveryEvidence(true, true, false, true, true, false, true, true, true, true);
    }

    #[Test]
    public function terminal_checkpoint_repair_requires_a_durable_committed_unit(): void
    {
        $decision = (new CrashBoundaryClassifier)->classify(
            CrashBoundary::AfterReconciliationBeforeCompletion,
            new RecoveryEvidence(true, true, false, false, false, false, true, true, false, true),
        );

        self::assertSame(RecoveryDisposition::ReconciliationFailed, $decision->disposition);
        self::assertFalse($decision->targetWritesPermitted);
    }

    #[Test]
    public function compatible_committed_unit_repairs_only_its_checkpoint_atomically(): void
    {
        $journal = new InMemoryRecoveryJournal;
        $coordinator = new AtomicRecoveryCoordinator($journal);
        $intent = new AtomicIntentDescriptor(
            hash('sha256', 'intent'),
            hash('sha256', 'idempotency'),
            AtomicUnit::PatientCore,
            MigrationState::CoreCommitting,
            1,
            hash('sha256', 'input'),
        );
        $evidence = new RecoveryEvidence(true, true, false, true, true, false, true, true, false, true);
        $checkpoint = new RecoveryCheckpoint('CORE_COMMITTED', hash('sha256', 'tx'), hash('sha256', 'writes'), hash('sha256', 'reconciliation'));

        $decision = $coordinator->recover($intent, CrashBoundary::AfterCoreCommitBeforeCheckpoint, $evidence, $checkpoint);

        self::assertSame(RecoveryDisposition::RepairCheckpointOnly, $decision->disposition);
        self::assertSame(1, $journal->transactions);
        self::assertSame(1, $journal->checkpointCount);
        self::assertSame(1, $journal->decisionCount);
        self::assertFalse($decision->targetWritesPermitted);
    }

    #[Test]
    public function compensation_is_explicit_and_unit_specific(): void
    {
        $registry = new CompensationPlanRegistry;
        foreach (AtomicUnit::cases() as $unit) {
            $plan = $registry->forUnit($unit);
            self::assertNotSame([], $plan->allowedActions);
            self::assertTrue($plan->operatorReviewRequired);
        }

        $this->expectException(RecoveryException::class);
        $registry->forUnit(AtomicUnit::ExistingTargetLink)->assertActionAllowed('mutate_existing_target');
    }
}

final class InMemoryCasStore implements CompareAndSetStateStore
{
    public function __construct(public StateSnapshot $snapshot) {}

    public function compareAndSet(string $recordKey, MigrationState $expectedState, int $expectedVersion, int $expectedAttemptCount, MigrationState $nextState, int $nextAttemptCount): ?StateSnapshot
    {
        if ($this->snapshot->recordKey !== $recordKey || $this->snapshot->state !== $expectedState
            || $this->snapshot->version !== $expectedVersion || $this->snapshot->attemptCount !== $expectedAttemptCount) {
            return null;
        }

        return $this->snapshot = new StateSnapshot($recordKey, $nextState, $expectedVersion + 1, $nextAttemptCount);
    }
}

final class InMemoryRecoveryJournal implements AtomicRecoveryJournal
{
    public int $transactions = 0;

    public int $checkpointCount = 0;

    public int $decisionCount = 0;

    public function transaction(Closure $operation): mixed
    {
        $this->transactions++;

        return $operation();
    }

    public function resolveOrCreateIntent(AtomicIntentDescriptor $intent): AtomicIntentSnapshot
    {
        return new AtomicIntentSnapshot($intent, $intent->expectedPriorState, 0);
    }

    public function appendCheckpoint(AtomicIntentSnapshot $intent, RecoveryCheckpoint $checkpoint): void
    {
        $this->checkpointCount++;
    }

    public function recordDecision(AtomicIntentSnapshot $intent, RecoveryDecision $decision): void
    {
        $this->decisionCount++;
    }
}
