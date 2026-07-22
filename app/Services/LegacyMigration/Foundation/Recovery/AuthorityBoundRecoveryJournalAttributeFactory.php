<?php

namespace App\Services\LegacyMigration\Foundation\Recovery;

use App\Services\LegacyMigration\Foundation\Security\CanonicalTypedMessageEncoder;
use App\Services\LegacyMigration\Foundation\Security\HmacTokenService;
use App\Services\LegacyMigration\Foundation\Security\ProtectedStoreOperationContext;
use App\Services\LegacyMigration\Foundation\Security\ProtectedToken;
use App\Services\LegacyMigration\Foundation\Security\TokenDomain;
use App\Services\LegacyMigration\Foundation\Security\TypedValue;

final readonly class AuthorityBoundRecoveryJournalAttributeFactory implements RecoveryJournalAttributeFactory
{
    public function __construct(
        private RecoveryJournalAuthority $authority,
        private HmacTokenService $tokens,
        private CanonicalTypedMessageEncoder $encoder,
    ) {}

    public function intentToken(string $idempotencyToken, AtomicUnit $unit, MigrationState $expectedPriorState, int $attempt, string $inputFingerprint): string
    {
        return $this->token('recovery', [
            'intent', $idempotencyToken, $unit->value, $expectedPriorState->value, (string) $attempt, $inputFingerprint,
        ])->lookupDigest();
    }

    public function readContext(string $recordKind, RecoveryCoordinateHint $hint): ProtectedStoreOperationContext
    {
        [$domain, $run, $source, $target, $fields] = match ($recordKind) {
            'idempotency' => ['idempotency', $hint->runId, $hint->sourceSnapshotId, $hint->targetSnapshotId, [
                'run_id', 'source_snapshot_id', 'target_snapshot_id', 'domain', 'contract_version',
                'transformation_version', 'canonicalization_version', 'hmac_key_version',
                'access_classification', 'retention_classification', 'input_fingerprint',
            ]],
            'run' => ['migration_run', $hint->runId, null, null, ['contract_bundle_id', 'state', 'access_classification', 'retention_classification']],
            'source_snapshot' => ['source_snapshot', $hint->runId, $hint->sourceSnapshotId, null, ['run_id', 'snapshot_kind', 'result_hash', 'contract_version', 'access_classification', 'retention_classification']],
            'target_snapshot' => ['target_snapshot', $hint->runId, null, $hint->targetSnapshotId, ['run_id', 'snapshot_kind', 'result_hash', 'contract_version', 'access_classification', 'retention_classification']],
            'contract_bundle' => ['migration_run', null, null, null, ['bundle_version', 'bundle_hash', 'access_classification', 'retention_classification']],
            'intent' => ['recovery', $hint->runId, null, null, [
                'run_id', 'idempotency_record_id', 'domain', 'unit_name', 'expected_prior_state',
                'state', 'attempt', 'transition_attempt_count', 'lock_version', 'contract_version',
                'transformation_version', 'canonicalization_version', 'hmac_key_version',
                'write_set_hash', 'zero_write_evidence_hash', 'access_classification', 'retention_classification',
            ]],
            'checkpoint' => ['recovery', $hint->runId, null, null, [
                'run_id', 'idempotency_record_id', 'atomic_intent_id', 'stage', 'state',
                'transaction_evidence_hash', 'write_set_hash', 'reconciliation_bundle_hash',
            ]],
            'crosswalk' => ['recovery', $hint->runId, $hint->sourceSnapshotId, $hint->targetSnapshotId, [
                'run_id', 'source_snapshot_id', 'target_snapshot_id', 'domain', 'branch', 'state', 'is_active', 'contract_version',
            ]],
            'provenance' => ['recovery', $hint->runId, $hint->sourceSnapshotId, null, [
                'run_id', 'source_snapshot_id', 'domain', 'target_outcome', 'state', 'contract_version',
            ]],
            'reconciliation' => ['idempotency', $hint->runId, $hint->sourceSnapshotId, $hint->targetSnapshotId, [
                'run_id', 'source_snapshot_id', 'target_snapshot_id', 'domain', 'mandatory',
                'measurement_complete', 'difference', 'tolerance', 'acceptance_result', 'evidence_bundle_hash', 'contract_version',
            ]],
            'reservation' => ['recovery', $hint->runId, null, null, [
                'run_id', 'idempotency_record_id', 'domain', 'sequence_ordinal', 'state',
                'consumption_classification', 'contract_version',
            ]],
            'compensation' => ['recovery', $hint->runId, null, null, [
                'run_id', 'atomic_intent_id', 'unit_name', 'recovery_classification', 'state',
            ]],
            'journal' => ['migration_audit', $hint->runId, $hint->sourceSnapshotId, $hint->targetSnapshotId, [
                'event_type', 'crash_boundary', 'recovery_disposition', 'decision_state', 'unit_name',
                'operator_review_required', 'target_writes_permitted', 'resulting_lock_version',
            ]],
            default => throw RecoveryException::failClosed('RECOVERY-PROTECTED-RECORD-KIND-INVALID'),
        };

        return $this->context('read', $domain, $run, $source, $target, $fields);
    }

    public function verifyCoordinate(VerifiedRecoveryCoordinate $coordinate): void
    {
        if (! hash_equals($this->authority->expectedContractBundleHash(), $coordinate->contractBundleHash)) {
            throw RecoveryException::failClosed('RECOVERY-CONTRACT-BUNDLE-HASH-MISMATCH');
        }
    }

    public function intent(AtomicIntentDescriptor $descriptor, VerifiedRecoveryCoordinate $coordinate): ProtectedRecoveryWrite
    {
        $encoded = $this->token('recovery', [
            'intent', $descriptor->idempotencyToken, $descriptor->unit->value,
            $descriptor->expectedPriorState->value, (string) $descriptor->attempt, $descriptor->inputFingerprint,
        ])->encode();
        $this->assertDigest($encoded, $descriptor->intentToken);

        return new ProtectedRecoveryWrite(
            $this->context('write', 'recovery', $coordinate->hint->runId, null, null),
            $this->baseAttributes($coordinate) + [
                'integrity_checksum' => $this->integrityDigest(['intent', $descriptor->intentToken]),
            ],
            ['intent_token' => ['encoded_token' => $encoded, 'domain' => 'recovery']],
        );
    }

    public function checkpoint(AtomicIntentSnapshot $intent, RecoveryCheckpoint $checkpoint, VerifiedRecoveryCoordinate $coordinate): ProtectedRecoveryWrite
    {
        $dependency = $this->token('recovery', ['checkpoint', $intent->descriptor->intentToken, $checkpoint->stage]);

        return new ProtectedRecoveryWrite(
            $this->context('write', 'recovery', $coordinate->hint->runId, null, null),
            $this->baseAttributes($coordinate) + [
                'attempt_contract_version' => $this->authority->contractVersion(),
                'output_fingerprint' => $this->integrityDigest(['checkpoint-output', $checkpoint->stage]),
                'integrity_checksum' => $this->integrityDigest(['checkpoint', $dependency->lookupDigest()]),
            ],
            ['dependency_chain_token' => ['encoded_token' => $dependency->encode(), 'domain' => 'recovery']],
        );
    }

    public function decision(AtomicIntentSnapshot $intent, RecoveryDecision $decision, RecoveryEvidence $evidence, VerifiedRecoveryCoordinate $coordinate, int $resultingLockVersion, int $attemptCount): ProtectedRecoveryWrite
    {
        return $this->journalWrite(
            ['decision', $intent->descriptor->intentToken, $decision->boundary->value, (string) $resultingLockVersion],
            $coordinate,
            ['evidence' => $evidence->toArray(), 'attempt_count' => $attemptCount],
        );
    }

    public function transition(AtomicIntentSnapshot $intent, MigrationState $next, VerifiedRecoveryCoordinate $coordinate, int $resultingVersion, int $resultingAttempt): ProtectedRecoveryWrite
    {
        return $this->journalWrite(
            ['transition', $intent->descriptor->intentToken, $intent->state->value, $next->value, (string) $resultingVersion],
            $coordinate,
            ['resulting_attempt' => $resultingAttempt],
        );
    }

    /** @param list<string> $parts @param array<string,mixed> $evidence */
    private function journalWrite(array $parts, VerifiedRecoveryCoordinate $coordinate, array $evidence): ProtectedRecoveryWrite
    {
        $journal = $this->token('migration_audit', $parts);

        return new ProtectedRecoveryWrite(
            $this->context('write', 'migration_audit', $coordinate->hint->runId, $coordinate->hint->sourceSnapshotId, $coordinate->hint->targetSnapshotId),
            $this->baseAttributes($coordinate) + [
                'transaction_evidence_hash' => $this->integrityDigest(['transaction', ...$parts]),
                'write_set_hash' => $this->integrityDigest(['write-set', ...$parts]),
                'crosswalk_evidence_hash' => $this->integrityDigest(['crosswalk', ...$parts]),
                'provenance_evidence_hash' => $this->integrityDigest(['provenance', ...$parts]),
                'reconciliation_evidence_hash' => $this->integrityDigest(['reconciliation', ...$parts]),
                'checkpoint_evidence_hash' => $this->integrityDigest(['checkpoint', ...$parts]),
                'compensation_evidence_hash' => $this->integrityDigest(['compensation', ...$parts]),
                'encrypted_evidence' => $evidence,
                'integrity_checksum' => $this->integrityDigest(['journal', $journal->lookupDigest()]),
            ],
            ['journal_token' => ['encoded_token' => $journal->encode(), 'domain' => 'migration_audit']],
        );
    }

    /** @return array<string,mixed> */
    private function baseAttributes(VerifiedRecoveryCoordinate $coordinate): array
    {
        return [
            'contract_version' => $this->authority->contractVersion(),
            'transformation_version' => $this->authority->transformationVersion(),
            'canonicalization_version' => $this->authority->canonicalizationVersion(),
            'token_environment' => $this->authority->environment(),
            'hmac_key_id' => $this->token('recovery', ['metadata-key'])->keyId(),
            'hmac_key_version' => $this->token('recovery', ['metadata-key'])->keyVersion(),
            'access_classification' => $this->authority->accessClassification(),
            'retention_classification' => $this->authority->retentionClassification(),
        ];
    }

    /** @param list<string> $fields */
    private function context(string $operation, string $domain, ?int $run, ?int $source, ?int $target, array $fields = []): ProtectedStoreOperationContext
    {
        return new ProtectedStoreOperationContext(
            'foundation_recovery', [$operation], [$domain], $this->authority->environment(),
            $run, $source, $target, $this->authority->accessClassification(),
            $this->authority->retentionClassification(), $this->authority->authorityReference(), $fields,
        );
    }

    /** @param list<string> $parts */
    private function token(string $domain, array $parts): ProtectedToken
    {
        return $this->tokens->tokenize(
            new TokenDomain($domain),
            $this->encoder->encode(array_map(static fn (string $part): TypedValue => TypedValue::string($part), ['recovery-journal/v1', ...$parts]), $this->authority->canonicalizationVersion()),
        );
    }

    /** @param list<string> $parts */
    private function integrityDigest(array $parts): string
    {
        return $this->token('artifact_integrity', $parts)->lookupDigest();
    }

    private function assertDigest(string $encoded, string $expected): void
    {
        $actual = ProtectedToken::parse($encoded)->lookupDigest();
        if (! hash_equals($expected, $actual)) {
            throw RecoveryException::failClosed('RECOVERY-INTENT-TOKEN-AUTHORITY-MISMATCH');
        }
    }
}
