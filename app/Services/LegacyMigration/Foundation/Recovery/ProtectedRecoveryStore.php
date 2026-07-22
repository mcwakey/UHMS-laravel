<?php

namespace App\Services\LegacyMigration\Foundation\Recovery;

use App\Models\LegacyMigration\AtomicIntent;
use App\Models\LegacyMigration\Checkpoint;
use App\Models\LegacyMigration\CompensationRecord;
use App\Models\LegacyMigration\ContractBundle;
use App\Models\LegacyMigration\Crosswalk;
use App\Models\LegacyMigration\IdempotencyRecord;
use App\Models\LegacyMigration\MigrationRun;
use App\Models\LegacyMigration\NumberReservation;
use App\Models\LegacyMigration\ProtectedFoundationModel;
use App\Models\LegacyMigration\ProvenanceRecord;
use App\Models\LegacyMigration\ReconciliationResult;
use App\Models\LegacyMigration\Snapshot;
use App\Services\LegacyMigration\Foundation\Security\ProtectedStoreOperationContext;
use App\Services\LegacyMigration\Foundation\Storage\ProtectedRecordProjection;
use App\Services\LegacyMigration\Foundation\Storage\ProtectedRecordSecurityRepository;
use App\Services\LegacyMigration\Foundation\Storage\RecoveryJournalRepository;
use App\Services\LegacyMigration\Foundation\Storage\RecoveryRepository;
use App\Services\LegacyMigration\Foundation\Storage\StorageIntegrityException;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Support\Facades\DB;

/** All durable recovery reads and writes cross keyed envelope and access-audit boundaries. */
final class ProtectedRecoveryStore
{
    public function __construct(
        private readonly RecoveryJournalAttributeFactory $attributes,
        private readonly RecoveryRepository $recovery,
        private readonly RecoveryJournalRepository $journal,
        private readonly ProtectedRecordSecurityRepository $security,
        private readonly ?string $connection = null,
    ) {}

    public function transaction(\Closure $operation): mixed
    {
        $this->security->assertConnection($this->connectionName());

        return $this->db()->transaction($operation, 3);
    }

    public function connectionName(): string
    {
        $name = trim((string) ($this->connection ?? config('database.default')));
        if ($name === '') {
            throw RecoveryException::failClosed('RECOVERY-CONNECTION-BOUNDARY-MISSING');
        }

        return $name;
    }

    public function coordinate(AtomicIntentDescriptor $descriptor): VerifiedRecoveryCoordinate
    {
        $this->assertTransaction();
        $rows = $this->db()->table('legacy_migration_idempotency_records')
            ->where('idempotency_token', $descriptor->idempotencyToken)
            ->lockForUpdate()
            ->limit(2)
            ->get(['id', 'run_id', 'source_snapshot_id', 'target_snapshot_id']);
        if ($rows->count() !== 1) {
            throw RecoveryException::failClosed('RECOVERY-IDEMPOTENCY-CARDINALITY');
        }
        $row = $rows->first();
        $hint = new RecoveryCoordinateHint((int) $row->id, (int) $row->run_id, (int) $row->source_snapshot_id, $row->target_snapshot_id === null ? null : (int) $row->target_snapshot_id);
        $idempotency = $this->security->readProjection(
            $this->attributes->readContext('idempotency', $hint),
            IdempotencyRecord::class,
            $hint->idempotencyRecordId,
            ['idempotency_token' => $descriptor->idempotencyToken, 'input_fingerprint' => $descriptor->inputFingerprint],
        );
        $this->assertFields($idempotency, ['run_id', 'source_snapshot_id', 'domain', 'contract_version', 'transformation_version', 'canonicalization_version', 'hmac_key_version', 'access_classification', 'retention_classification']);
        if ((int) $idempotency->fields['run_id'] !== $hint->runId
            || (int) $idempotency->fields['source_snapshot_id'] !== $hint->sourceSnapshotId
            || ($idempotency->fields['target_snapshot_id'] === null ? null : (int) $idempotency->fields['target_snapshot_id']) !== $hint->targetSnapshotId) {
            throw RecoveryException::failClosed('RECOVERY-IDEMPOTENCY-LINEAGE-CONFLICT');
        }

        $run = $this->projection('run', MigrationRun::class, $hint->runId, $hint);
        $bundleId = (int) ($run->fields['contract_bundle_id'] ?? 0);
        $verifiedHint = new RecoveryCoordinateHint($hint->idempotencyRecordId, $hint->runId, $hint->sourceSnapshotId, $hint->targetSnapshotId, $bundleId);
        $source = $this->projection('source_snapshot', Snapshot::class, $hint->sourceSnapshotId, $verifiedHint);
        if (($source->fields['snapshot_kind'] ?? null) !== 'source' || (int) ($source->fields['run_id'] ?? 0) !== $hint->runId) {
            throw RecoveryException::failClosed('RECOVERY-SOURCE-SNAPSHOT-LINEAGE-CONFLICT');
        }
        $target = null;
        if ($hint->targetSnapshotId !== null) {
            $target = $this->projection('target_snapshot', Snapshot::class, $hint->targetSnapshotId, $verifiedHint);
            if (($target->fields['snapshot_kind'] ?? null) !== 'target' || (int) ($target->fields['run_id'] ?? 0) !== $hint->runId) {
                throw RecoveryException::failClosed('RECOVERY-TARGET-SNAPSHOT-LINEAGE-CONFLICT');
            }
        }
        $bundle = $this->projection('contract_bundle', ContractBundle::class, $bundleId, $verifiedHint);
        $bundleVersion = (string) ($bundle->fields['bundle_version'] ?? '');
        foreach ([$idempotency, $source, $target] as $record) {
            if ($record !== null && ! hash_equals($bundleVersion, (string) ($record->fields['contract_version'] ?? ''))) {
                throw RecoveryException::failClosed('RECOVERY-CONTRACT-VERSION-LINEAGE-CONFLICT');
            }
        }
        $coordinate = new VerifiedRecoveryCoordinate(
            $verifiedHint,
            (string) $idempotency->fields['domain'],
            $descriptor->inputFingerprint,
            (string) ($source->fields['result_hash'] ?? ''),
            $target === null ? null : (string) ($target->fields['result_hash'] ?? ''),
            (string) ($bundle->fields['bundle_hash'] ?? ''),
        );
        $this->attributes->verifyCoordinate($coordinate);

        return $coordinate;
    }

    /** Resolve allocator lineage through the same keyed-envelope boundary as recovery. */
    public function reservationCoordinate(string $idempotencyToken, string $protectedSourceToken): VerifiedRecoveryCoordinate
    {
        $this->assertTransaction();
        $rows = $this->db()->table('legacy_migration_idempotency_records')
            ->where('idempotency_token', $idempotencyToken)
            ->lockForUpdate()
            ->limit(2)
            ->get(['id', 'run_id', 'source_snapshot_id', 'target_snapshot_id']);
        if ($rows->count() !== 1) {
            throw RecoveryException::failClosed('RECOVERY-IDEMPOTENCY-CARDINALITY');
        }
        $row = $rows->first();
        $hint = new RecoveryCoordinateHint((int) $row->id, (int) $row->run_id, (int) $row->source_snapshot_id, $row->target_snapshot_id === null ? null : (int) $row->target_snapshot_id);
        if ($hint->targetSnapshotId === null) {
            throw RecoveryException::failClosed('RECOVERY-TARGET-SNAPSHOT-LINEAGE-CONFLICT');
        }
        $idempotency = $this->security->readProjection(
            $this->attributes->readContext('idempotency', $hint),
            IdempotencyRecord::class,
            $hint->idempotencyRecordId,
            ['idempotency_token' => $idempotencyToken, 'protected_source_token' => $protectedSourceToken, 'domain' => 'patient_core'],
        );
        $this->assertFields($idempotency, ['run_id', 'source_snapshot_id', 'target_snapshot_id', 'domain', 'input_fingerprint', 'contract_version']);
        if ((int) $idempotency->fields['run_id'] !== $hint->runId
            || (int) $idempotency->fields['source_snapshot_id'] !== $hint->sourceSnapshotId
            || (int) $idempotency->fields['target_snapshot_id'] !== $hint->targetSnapshotId) {
            throw RecoveryException::failClosed('RECOVERY-IDEMPOTENCY-LINEAGE-CONFLICT');
        }

        $run = $this->projection('run', MigrationRun::class, $hint->runId, $hint);
        $bundleId = (int) ($run->fields['contract_bundle_id'] ?? 0);
        $verifiedHint = new RecoveryCoordinateHint($hint->idempotencyRecordId, $hint->runId, $hint->sourceSnapshotId, $hint->targetSnapshotId, $bundleId);
        $source = $this->projection('source_snapshot', Snapshot::class, $hint->sourceSnapshotId, $verifiedHint);
        $target = $this->projection('target_snapshot', Snapshot::class, $hint->targetSnapshotId, $verifiedHint);
        if (($source->fields['snapshot_kind'] ?? null) !== 'source'
            || ($target->fields['snapshot_kind'] ?? null) !== 'target'
            || (int) ($source->fields['run_id'] ?? 0) !== $hint->runId
            || (int) ($target->fields['run_id'] ?? 0) !== $hint->runId) {
            throw RecoveryException::failClosed('RECOVERY-SNAPSHOT-LINEAGE-CONFLICT');
        }
        $bundle = $this->projection('contract_bundle', ContractBundle::class, $bundleId, $verifiedHint);
        $bundleVersion = (string) ($bundle->fields['bundle_version'] ?? '');
        foreach ([$idempotency, $source, $target] as $record) {
            if (! hash_equals($bundleVersion, (string) ($record->fields['contract_version'] ?? ''))) {
                throw RecoveryException::failClosed('RECOVERY-CONTRACT-VERSION-LINEAGE-CONFLICT');
            }
        }
        $coordinate = new VerifiedRecoveryCoordinate(
            $verifiedHint,
            (string) $idempotency->fields['domain'],
            (string) $idempotency->fields['input_fingerprint'],
            (string) ($source->fields['result_hash'] ?? ''),
            (string) ($target->fields['result_hash'] ?? ''),
            (string) ($bundle->fields['bundle_hash'] ?? ''),
        );
        $this->attributes->verifyCoordinate($coordinate);

        return $coordinate;
    }

    public function resolveOrCreateIntent(AtomicIntentDescriptor $descriptor, VerifiedRecoveryCoordinate $coordinate): AtomicIntentSnapshot
    {
        $this->assertTransaction();
        $existingId = $this->db()->table('legacy_migration_atomic_intents')->where('intent_token', $descriptor->intentToken)->lockForUpdate()->value('id');
        if ($existingId === null) {
            $write = $this->attributes->intent($descriptor, $coordinate);
            $record = $this->recovery->createIntentProtected($write->context, array_replace($write->attributes, [
                'run_id' => $coordinate->hint->runId,
                'idempotency_record_id' => $coordinate->hint->idempotencyRecordId,
                'domain' => $coordinate->domain,
                'unit_name' => $descriptor->unit->value,
                'expected_prior_state' => $descriptor->expectedPriorState->value,
                'state' => $descriptor->expectedPriorState->value,
                'attempt' => $descriptor->attempt,
                'transition_attempt_count' => 0,
                'lock_version' => 0,
            ]), $write->tokenSet);
            $existingId = (int) $record->id;
        }

        $projection = $this->security->readProjection(
            $this->attributes->readContext('intent', $coordinate->hint),
            AtomicIntent::class,
            (int) $existingId,
            [
                'intent_token' => $descriptor->intentToken,
                'idempotency_record_id' => $coordinate->hint->idempotencyRecordId,
                'run_id' => $coordinate->hint->runId,
                'unit_name' => $descriptor->unit->value,
                'expected_prior_state' => $descriptor->expectedPriorState->value,
                'attempt' => $descriptor->attempt,
            ],
        );
        $state = MigrationState::tryFrom((string) ($projection->fields['state'] ?? ''));
        if ($state === null) {
            throw RecoveryException::failClosed('RECOVERY-INTENT-STATE-INVALID');
        }

        return new AtomicIntentSnapshot($descriptor, $state, (int) ($projection->fields['lock_version'] ?? -1));
    }

    /** Observe only sealed durable records at the exact protected coordinate. */
    public function observe(AtomicIntentSnapshot $intent, CrashBoundary $boundary, VerifiedRecoveryCoordinate $coordinate): RecoveryObservation
    {
        $this->assertTransaction();
        $intentId = $this->intentId($intent);
        $intentProjection = $this->security->readProjection(
            $this->attributes->readContext('intent', $coordinate->hint),
            AtomicIntent::class,
            $intentId,
            ['run_id' => $coordinate->hint->runId, 'idempotency_record_id' => $coordinate->hint->idempotencyRecordId],
        );
        $sourceToken = $this->db()->table('legacy_migration_idempotency_records')
            ->where('id', $coordinate->hint->idempotencyRecordId)
            ->value('protected_source_token');

        $crosswalkIds = $this->ids('legacy_migration_crosswalks', [
            'run_id' => $coordinate->hint->runId,
            'source_snapshot_id' => $coordinate->hint->sourceSnapshotId,
            'idempotency_token' => $intent->descriptor->idempotencyToken,
        ]);
        $provenanceIds = $sourceToken === null ? [] : $this->ids('legacy_migration_provenance_records', [
            'run_id' => $coordinate->hint->runId,
            'source_snapshot_id' => $coordinate->hint->sourceSnapshotId,
            'protected_source_token' => (string) $sourceToken,
        ]);
        $reconciliationIds = $this->ids('legacy_migration_reconciliation_results', [
            'run_id' => $coordinate->hint->runId,
            'source_snapshot_id' => $coordinate->hint->sourceSnapshotId,
            'idempotency_token' => $intent->descriptor->idempotencyToken,
        ]);
        $reservationIds = $this->ids('legacy_migration_number_reservations', [
            'run_id' => $coordinate->hint->runId,
            'idempotency_record_id' => $coordinate->hint->idempotencyRecordId,
        ]);
        $checkpointIds = $this->ids('legacy_migration_checkpoints', [
            'run_id' => $coordinate->hint->runId,
            'idempotency_record_id' => $coordinate->hint->idempotencyRecordId,
            'atomic_intent_id' => $intentId,
        ]);
        $compensationIds = $this->ids('legacy_migration_compensation_records', [
            'run_id' => $coordinate->hint->runId,
            'atomic_intent_id' => $intentId,
        ]);

        $crosswalk = $this->oneProjection('crosswalk', Crosswalk::class, $crosswalkIds, $coordinate, ['run_id' => $coordinate->hint->runId]);
        $provenance = $this->oneProjection('provenance', ProvenanceRecord::class, $provenanceIds, $coordinate, ['run_id' => $coordinate->hint->runId]);
        $reconciliation = $this->oneProjection('reconciliation', ReconciliationResult::class, $reconciliationIds, $coordinate, ['run_id' => $coordinate->hint->runId]);
        $reservation = $this->oneProjection('reservation', NumberReservation::class, $reservationIds, $coordinate, ['run_id' => $coordinate->hint->runId]);
        $checkpoint = $this->oneProjection('checkpoint', Checkpoint::class, $checkpointIds, $coordinate, ['run_id' => $coordinate->hint->runId]);
        $compensation = $this->oneProjection('compensation', CompensationRecord::class, $compensationIds, $coordinate, ['run_id' => $coordinate->hint->runId]);

        $unexpected = count($crosswalkIds) > 1 || count($provenanceIds) > 1 || count($reconciliationIds) > 1
            || count($reservationIds) > 1 || count($checkpointIds) > 1 || count($compensationIds) > 1
            || $compensation !== null;
        $hasCrosswalk = $crosswalk !== null && ($crosswalk->fields['is_active'] ?? false) === true;
        $hasProvenance = $provenance !== null && in_array((string) ($provenance->fields['target_outcome'] ?? ''), ['created', 'linked_existing', 'committed'], true);
        $durableCommitted = $hasCrosswalk || $hasProvenance;
        $durableComplete = $hasCrosswalk && $hasProvenance;
        $reconciliationPresent = $reconciliation !== null && ($reconciliation->fields['mandatory'] ?? false) === true;
        $reconciliationPassed = $reconciliationPresent
            && ($reconciliation->fields['measurement_complete'] ?? false) === true
            && (string) ($reconciliation->fields['acceptance_result'] ?? '') === 'passed'
            && $this->zero($reconciliation->fields['difference'] ?? null)
            && $this->zero($reconciliation->fields['tolerance'] ?? null);
        $allocationExplained = $boundary !== CrashBoundary::AfterNumberAllocationBeforePatientCommit
            || ($reservation !== null && in_array((string) ($reservation->fields['consumption_classification'] ?? ''), ['committed', 'explained'], true));
        $zeroWrite = (string) ($intentProjection->fields['zero_write_evidence_hash'] ?? '');
        $rolledBack = preg_match('/\A[a-f0-9]{64}\z/D', $zeroWrite) === 1
            && ! $durableCommitted && $reservation === null;

        $facts = [
            'coordinatesMatch' => true,
            'lineageCompatible' => true,
            'unexpectedDurableFacts' => $unexpected,
            'durableUnitCommitted' => $durableCommitted,
            'durableFactsComplete' => $durableComplete,
            'checkpointPresent' => $checkpoint !== null,
            'mandatoryReconciliationPresent' => $reconciliationPresent,
            'reconciliationPassed' => $reconciliationPassed,
            'transactionRolledBack' => $rolledBack,
            'allocationConsumptionExplained' => $allocationExplained,
        ];
        $evidence = RecoveryEvidence::fromProtectedStore($this, $facts);
        $evidenceHash = hash('sha256', json_encode([
            'coordinate' => [$coordinate->hint->runId, $coordinate->hint->sourceSnapshotId, $coordinate->hint->targetSnapshotId, $coordinate->hint->contractBundleId],
            'intent' => $intentId,
            'crosswalk' => $crosswalkIds,
            'provenance' => $provenanceIds,
            'reconciliation' => $reconciliationIds,
            'reservation' => $reservationIds,
            'checkpoint' => $checkpointIds,
            'compensation' => $compensationIds,
            'facts' => $facts,
        ], JSON_THROW_ON_ERROR));
        $checkpointRepair = $boundary === CrashBoundary::AfterCoreCommitBeforeCheckpoint
            && $durableComplete && $reconciliationPassed && $checkpoint === null
                ? new RecoveryCheckpoint(
                    'CORE_COMMITTED',
                    hash('sha256', 'transaction|'.$evidenceHash),
                    hash('sha256', 'write-set|'.$evidenceHash),
                    (string) $reconciliation->fields['evidence_bundle_hash'],
                )
                : null;

        return RecoveryObservation::fromProtectedStore($this, $boundary, $evidence, $checkpointRepair, $evidenceHash);
    }

    public function appendCheckpoint(AtomicIntentSnapshot $intent, RecoveryCheckpoint $checkpoint, VerifiedRecoveryCoordinate $coordinate): void
    {
        $intentId = $this->intentId($intent);
        $existingId = $this->db()->table('legacy_migration_checkpoints')->where('atomic_intent_id', $intentId)->where('stage', $checkpoint->stage)->lockForUpdate()->value('id');
        $expected = [
            'atomic_intent_id' => $intentId,
            'stage' => $checkpoint->stage,
            'transaction_evidence_hash' => $checkpoint->transactionEvidenceHash,
            'write_set_hash' => $checkpoint->writeSetHash,
            'reconciliation_bundle_hash' => $checkpoint->reconciliationBundleHash,
        ];
        if ($existingId !== null) {
            $this->security->readProjection($this->attributes->readContext('checkpoint', $coordinate->hint), Checkpoint::class, (int) $existingId, $expected);

            return;
        }
        $write = $this->attributes->checkpoint($intent, $checkpoint, $coordinate);
        $this->recovery->appendCheckpointProtected($write->context, array_replace($write->attributes, $expected, [
            'run_id' => $coordinate->hint->runId,
            'idempotency_record_id' => $coordinate->hint->idempotencyRecordId,
            'domain' => $coordinate->domain,
            'expected_prior_state' => $intent->state->value,
            'state' => $intent->state->value,
            'input_fingerprint' => $coordinate->inputFingerprint,
        ]), $write->tokenSet);
    }

    public function existingDecision(AtomicIntentSnapshot $intent, RecoveryDecision $decision, string $durableEvidenceHash, VerifiedRecoveryCoordinate $coordinate): bool
    {
        $id = $this->db()->table('legacy_migration_recovery_journal_entries')
            ->where('atomic_intent_id', $this->intentId($intent))
            ->where('event_type', 'recovery_decision')
            ->where('crash_boundary', $decision->boundary->value)
            ->value('id');
        if ($id === null) {
            return false;
        }
        $this->journal->readProtected($this->attributes->readContext('journal', $coordinate->hint), (int) $id, [
            'recovery_disposition' => $decision->disposition->value,
            'decision_state' => $decision->state->value,
            'transaction_evidence_hash' => $durableEvidenceHash,
        ]);

        return true;
    }

    public function replayDecision(AtomicIntentSnapshot $intent, CrashBoundary $boundary, RecoveryObservation $observation, VerifiedRecoveryCoordinate $coordinate): ?RecoveryDecision
    {
        $id = $this->db()->table('legacy_migration_recovery_journal_entries')
            ->where('atomic_intent_id', $this->intentId($intent))
            ->where('event_type', 'recovery_decision')
            ->where('crash_boundary', $boundary->value)
            ->value('id');
        if ($id === null) {
            return null;
        }
        try {
            $projection = $this->journal->readProtected(
                $this->attributes->readContext('journal', $coordinate->hint),
                (int) $id,
                [
                    'crash_boundary' => $boundary->value,
                    'transaction_evidence_hash' => $observation->durableEvidenceHash,
                ],
            );
        } catch (StorageIntegrityException) {
            throw RecoveryException::failClosed('RECOVERY-RECORDED-EVIDENCE-CHANGED');
        }
        $disposition = RecoveryDisposition::tryFrom((string) ($projection->fields['recovery_disposition'] ?? ''));
        $state = MigrationState::tryFrom((string) ($projection->fields['decision_state'] ?? ''));
        $unit = AtomicUnit::tryFrom((string) ($projection->fields['unit_name'] ?? ''));
        if ($disposition === null || $state === null || $unit === null || $unit !== $intent->descriptor->unit) {
            throw RecoveryException::failClosed('RECOVERY-RECORDED-DECISION-INVALID');
        }

        return new RecoveryDecision(
            $boundary,
            $disposition,
            $state,
            $unit,
            (bool) ($projection->fields['operator_review_required'] ?? true),
            (bool) ($projection->fields['target_writes_permitted'] ?? false),
        );
    }

    public function recordDecision(AtomicIntentSnapshot $intent, RecoveryDecision $decision, RecoveryEvidence $evidence, string $durableEvidenceHash, VerifiedRecoveryCoordinate $coordinate, int $attemptCount): void
    {
        $write = $this->attributes->decision($intent, $decision, $evidence, $coordinate, $intent->lockVersion, $attemptCount);
        $this->journal->appendProtected($write, $this->journalFixed($intent, $coordinate) + [
            'event_type' => 'recovery_decision',
            'crash_boundary' => $decision->boundary->value,
            'recovery_disposition' => $decision->disposition->value,
            'decision_state' => $decision->state->value,
            'resulting_lock_version' => $intent->lockVersion,
            'resulting_attempt_count' => $attemptCount,
            'target_writes_permitted' => $decision->targetWritesPermitted,
            'operator_review_required' => $decision->operatorReviewRequired,
            'transaction_evidence_hash' => $durableEvidenceHash,
        ]);
    }

    public function currentAttemptCount(AtomicIntentSnapshot $intent): int
    {
        $value = $this->db()->table('legacy_migration_atomic_intents')
            ->where('id', $this->intentId($intent))
            ->value('transition_attempt_count');
        if ($value === null || (int) $value < 0) {
            throw RecoveryException::failClosed('RECOVERY-INTENT-ATTEMPT-INVALID');
        }

        return (int) $value;
    }

    public function compareAndSet(string $recordKey, MigrationState $expectedState, int $expectedVersion, int $expectedAttempt, MigrationState $nextState, int $nextAttempt): ?StateSnapshot
    {
        MonotonicStateMachine::assertTransitionAllowed($expectedState, $nextState);

        return $this->transaction(function () use ($recordKey, $expectedState, $expectedVersion, $expectedAttempt, $nextState, $nextAttempt): ?StateSnapshot {
            $row = $this->db()->table('legacy_migration_atomic_intents')->where('intent_token', $recordKey)->lockForUpdate()->first(['id', 'idempotency_record_id']);
            if ($row === null || $nextAttempt !== $expectedAttempt + 1) {
                return null;
            }
            $idempotency = $this->db()->table('legacy_migration_idempotency_records')->where('id', $row->idempotency_record_id)->first(['idempotency_token', 'input_fingerprint']);
            if ($idempotency === null) {
                throw RecoveryException::failClosed('RECOVERY-IDEMPOTENCY-MISSING');
            }
            $descriptor = new AtomicIntentDescriptor($recordKey, (string) $idempotency->idempotency_token, AtomicUnit::PatientCore, $expectedState, 1, (string) $idempotency->input_fingerprint);
            $coordinate = $this->coordinate($descriptor);
            $intent = $this->security->readProjection($this->attributes->readContext('intent', $coordinate->hint), AtomicIntent::class, (int) $row->id);
            $unit = AtomicUnit::tryFrom((string) ($intent->fields['unit_name'] ?? ''));
            if ($unit === null) {
                throw RecoveryException::failClosed('RECOVERY-INTENT-UNIT-MISMATCH');
            }
            $descriptor = new AtomicIntentDescriptor($recordKey, (string) $idempotency->idempotency_token, $unit, MigrationState::from((string) $intent->fields['expected_prior_state']), (int) $intent->fields['attempt'], (string) $idempotency->input_fingerprint);
            if (($intent->fields['state'] ?? null) !== $expectedState->value
                || (int) ($intent->fields['lock_version'] ?? -1) !== $expectedVersion
                || (int) ($intent->fields['transition_attempt_count'] ?? -1) !== $expectedAttempt) {
                return null;
            }
            $snapshot = new AtomicIntentSnapshot($descriptor, $expectedState, $expectedVersion);
            $write = $this->attributes->transition($snapshot, $nextState, $coordinate, $expectedVersion + 1, $nextAttempt);
            $this->journal->transitionIntentProtected($this->transitionContext($coordinate), (int) $row->id, $expectedState->value, $expectedVersion, $expectedAttempt, $nextState->value, $nextAttempt);
            $this->journal->appendProtected($write, $this->journalFixed($snapshot, $coordinate) + [
                'event_type' => 'state_transition',
                'decision_state' => $nextState->value,
                'resulting_lock_version' => $expectedVersion + 1,
                'resulting_attempt_count' => $nextAttempt,
            ]);

            return new StateSnapshot($recordKey, $nextState, $expectedVersion + 1, $nextAttempt);
        });
    }

    private function transitionContext(VerifiedRecoveryCoordinate $coordinate): ProtectedStoreOperationContext
    {
        $read = $this->attributes->readContext('intent', $coordinate->hint);

        return new ProtectedStoreOperationContext(
            $read->purpose(), ['transition'], ['recovery'], $read->environment(), $coordinate->hint->runId,
            null, null, $read->accessClassification(), $read->retentionClassification(), $read->authorityReference(),
            ['state', 'lock_version', 'transition_attempt_count'],
        );
    }

    /** @return array<string,mixed> */
    private function journalFixed(AtomicIntentSnapshot $intent, VerifiedRecoveryCoordinate $coordinate): array
    {
        return [
            'run_id' => $coordinate->hint->runId,
            'atomic_intent_id' => $this->intentId($intent),
            'idempotency_record_id' => $coordinate->hint->idempotencyRecordId,
            'source_snapshot_id' => $coordinate->hint->sourceSnapshotId,
            'target_snapshot_id' => $coordinate->hint->targetSnapshotId,
            'contract_bundle_id' => $coordinate->hint->contractBundleId,
            'unit_name' => $intent->descriptor->unit->value,
            'expected_state' => $intent->state->value,
            'observed_state' => $intent->state->value,
            'expected_lock_version' => $intent->lockVersion,
            'expected_attempt_count' => (int) $this->db()->table('legacy_migration_atomic_intents')->where('id', $this->intentId($intent))->value('transition_attempt_count'),
            'target_writes_permitted' => false,
            'operator_review_required' => false,
            'input_fingerprint' => $coordinate->inputFingerprint,
            'source_snapshot_fingerprint' => $coordinate->sourceSnapshotFingerprint,
            'target_snapshot_fingerprint' => $coordinate->targetSnapshotFingerprint,
            'contract_bundle_hash' => $coordinate->contractBundleHash,
        ];
    }

    private function intentId(AtomicIntentSnapshot $intent): int
    {
        $id = $this->db()->table('legacy_migration_atomic_intents')->where('intent_token', $intent->descriptor->intentToken)->value('id');
        if ($id === null) {
            throw RecoveryException::failClosed('RECOVERY-INTENT-MISSING');
        }

        return (int) $id;
    }

    /** @param array<string,int|string> $where @return list<int> */
    private function ids(string $table, array $where): array
    {
        $query = $this->db()->table($table);
        foreach ($where as $field => $value) {
            $query->where($field, $value);
        }

        return $query->orderBy('id')->limit(3)->pluck('id')->map(static fn ($id): int => (int) $id)->all();
    }

    /** @param class-string<ProtectedFoundationModel> $type @param list<int> $ids @param array<string,mixed> $expected */
    private function oneProjection(string $kind, string $type, array $ids, VerifiedRecoveryCoordinate $coordinate, array $expected): ?ProtectedRecordProjection
    {
        if ($ids === []) {
            return null;
        }

        return $this->security->readProjection($this->attributes->readContext($kind, $coordinate->hint), $type, $ids[0], $expected);
    }

    private function zero(mixed $value): bool
    {
        return $value !== null && preg_match('/\A[+-]?0+(?:\.0+)?\z/D', (string) $value) === 1;
    }

    /** @param class-string<ProtectedFoundationModel> $type */
    private function projection(string $kind, string $type, int $id, RecoveryCoordinateHint $hint): ProtectedRecordProjection
    {
        return $this->security->readProjection($this->attributes->readContext($kind, $hint), $type, $id);
    }

    /** @param list<string> $required */
    private function assertFields(ProtectedRecordProjection $projection, array $required): void
    {
        foreach ($required as $field) {
            if (! array_key_exists($field, $projection->fields)) {
                throw RecoveryException::failClosed('RECOVERY-PROTECTED-PROJECTION-INCOMPLETE');
            }
        }
    }

    private function assertTransaction(): void
    {
        if ($this->db()->transactionLevel() < 1) {
            throw RecoveryException::failClosed('RECOVERY-DURABLE-TRANSACTION-REQUIRED');
        }
    }

    private function db(): ConnectionInterface
    {
        $name = $this->connectionName();
        if ($name !== (string) config('database.default')) {
            throw RecoveryException::failClosed('RECOVERY-PROTECTED-CONNECTION-MISMATCH');
        }

        return DB::connection($name);
    }
}
