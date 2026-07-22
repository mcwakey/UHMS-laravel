<?php

namespace App\Services\LegacyMigration\Foundation\Security;

use App\Models\LegacyMigration\ProvenanceRecord;
use App\Models\LegacyMigration\Remediation;
use App\Services\LegacyMigration\Foundation\Storage\ProtectedRecordSecurityRepository;

/** Non-forgeable, purpose-specific authority over one actual sealed evidence row. */
final class EnvelopeVerifiedIntegrityAuthority
{
    private function __construct(
        private readonly ProtectedRecordSecurityRepository $repository,
        private readonly ProtectedStoreOperationContext $context,
        private readonly string $recordType,
        private readonly int $recordId,
        private readonly string $purpose,
    ) {}

    public static function remediation(
        ProtectedRecordSecurityRepository $repository,
        ProtectedStoreOperationContext $context,
        int $recordId,
    ): self {
        return new self($repository, $context, Remediation::class, $recordId, 'remediation');
    }

    public static function provenance(
        ProtectedRecordSecurityRepository $repository,
        ProtectedStoreOperationContext $context,
        int $recordId,
    ): self {
        return new self($repository, $context, ProvenanceRecord::class, $recordId, 'provenance');
    }

    public function assertRemediationCandidate(RemediationCandidate $candidate): void
    {
        $this->assertPurpose('remediation');
        if ($candidate->targetSnapshotId !== null) {
            throw ProtectedStoreAccessDeniedException::forCode('LM-SEC-REMEDIATION-TARGET-COORDINATE-001');
        }
        $claims = [
            'remediation_reference' => $candidate->remediationReference,
            'source_token_reference' => $candidate->sourceTokenReference,
            'field_rule' => $candidate->fieldRule,
            'value_fingerprint' => $candidate->valueFingerprint,
            'precedence_ordinal' => $candidate->precedenceOrdinal,
            'approved' => $candidate->approved,
            'revoked' => $candidate->revoked,
            'conflicted' => $candidate->conflicted,
            'superseded_by_reference' => $candidate->supersededByReference,
            'approved_at' => $candidate->approvedAt->format(DATE_ATOM),
            'expires_at' => $candidate->expiresAt?->format(DATE_ATOM),
            'run_id' => $candidate->runId,
            'source_snapshot_id' => $candidate->sourceSnapshotId,
            'target_snapshot_id' => null,
            'authority_evidence_reference' => $candidate->authorityEvidenceReference,
        ];
        $this->repository->readProjection($this->context, $this->recordType, $this->recordId, [
            'run_id' => $candidate->runId,
            'source_snapshot_id' => $candidate->sourceSnapshotId,
            'protected_source_token' => $candidate->sourceTokenReference,
            'remediation_token' => $candidate->remediationReference,
            'supersedes_token' => $candidate->supersededByReference,
            'evidence_type' => $candidate->fieldRule,
            'approval_state' => $candidate->approved ? 'approved' : 'not_approved',
            'has_unresolved_conflict' => $candidate->conflicted ? 1 : 0,
            'valid_from' => $candidate->approvedAt->format('Y-m-d H:i:s'),
            'valid_until' => $candidate->expiresAt?->format('Y-m-d H:i:s'),
            'revoked_at' => $candidate->revoked ? $candidate->approvedAt->format('Y-m-d H:i:s') : null,
            'integrity_checksum' => self::claimHash('remediation', $claims),
        ]);
    }

    /** @param array<string,mixed> $outcome */
    public function assertPatientProvenance(array $outcome, int $runId, int $sourceSnapshotId, ?int $targetSnapshotId): void
    {
        $this->assertPurpose('provenance');
        if ($targetSnapshotId !== null) {
            throw ProtectedStoreAccessDeniedException::forCode('LM-SEC-PROVENANCE-TARGET-COORDINATE-001');
        }
        $claims = $outcome;
        unset($claims['integrity_authority']);
        $claims = array_replace($claims, [
            'run_id' => $runId,
            'source_snapshot_id' => $sourceSnapshotId,
            'target_snapshot_id' => null,
        ]);
        $this->repository->readProjection($this->context, $this->recordType, $this->recordId, [
            'run_id' => $runId,
            'source_snapshot_id' => $sourceSnapshotId,
            'protected_source_token' => $outcome['source_token_reference'] ?? null,
            'patient_root_token' => $outcome['root_reference'] ?? null,
            'subchain_token' => $outcome['subchain_reference'] ?? null,
            'outcome_coordinate_token' => $outcome['outcome_token_reference'] ?? null,
            'source_query_id' => $outcome['source_contract'] ?? null,
            'query_hash' => $outcome['query_evidence'] ?? null,
            'target_outcome' => $outcome['disposition'] ?? null,
            'transformation_version' => $outcome['transformation_version'] ?? null,
            'integrity_checksum' => self::claimHash('patient_provenance', $claims),
        ]);
    }

    /** @param array<string,mixed> $outcome */
    public function assertInsuranceProvenance(array $outcome): void
    {
        $this->assertPurpose('provenance');
        $claims = $outcome;
        unset($claims['integrity_authority']);
        $this->repository->readProjection($this->context, $this->recordType, $this->recordId, [
            'protected_source_token' => $outcome['source_row_reference'] ?? null,
            'protected_target_token' => $outcome['history_outcome_reference'] ?? null,
            'target_outcome' => 'protected_history',
            'integrity_checksum' => self::claimHash('insurance_provenance', $claims),
        ]);
    }

    /** @param array<string,mixed> $claims */
    public static function claimHash(string $purpose, array $claims): string
    {
        ksort($claims, SORT_STRING);

        return hash('sha256', "legacy-migration/verified-claims/v1\0{$purpose}\0".json_encode(
            $claims,
            JSON_THROW_ON_ERROR | JSON_PRESERVE_ZERO_FRACTION | JSON_UNESCAPED_SLASHES,
        ));
    }

    private function assertPurpose(string $purpose): void
    {
        if (! hash_equals($purpose, $this->purpose)) {
            throw ProtectedStoreAccessDeniedException::forCode('LM-SEC-INTEGRITY-AUTHORITY-PURPOSE-001');
        }
    }
}
