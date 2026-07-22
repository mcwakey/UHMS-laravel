<?php

namespace App\Services\LegacyMigration\Foundation\Security;

final class ProvenanceCompletenessValidator
{
    private const PRIMARY_DISPOSITIONS = [
        'mapped', 'protected_history', 'quarantined', 'policy_excluded', 'not_evidenced',
    ];

    /**
     * @param  list<string>  $consumedFieldContracts
     * @param  list<array{field_contract:string, disposition:string, source_contract:string, transformation_version:string, query_evidence:string, result_evidence:string, root_reference:string, subchain_reference:string, run_id:int, source_snapshot_id:int, target_snapshot_id:?int, integrity_authority:EnvelopeVerifiedIntegrityAuthority}>  $primaryOutcomes
     * @param  list<array{field_contract:string, ordinal:int, exception_code:string}>  $secondaryExceptions
     */
    public function assertPatientFieldsComplete(
        array $consumedFieldContracts,
        array $primaryOutcomes,
        array $secondaryExceptions,
        int $runId,
        int $sourceSnapshotId,
        ?int $targetSnapshotId,
    ): void {
        $expected = array_fill_keys($consumedFieldContracts, 0);
        if (count($expected) !== count($consumedFieldContracts)) {
            throw ProtectedStoreAccessDeniedException::forCode('LM-SEC-PROVENANCE-DUPLICATE-CONTRACT-001');
        }
        foreach ($primaryOutcomes as $outcome) {
            $authority = $outcome['integrity_authority'] ?? null;
            if (! $authority instanceof EnvelopeVerifiedIntegrityAuthority) {
                throw ProtectedStoreAccessDeniedException::forCode('LM-SEC-PROVENANCE-INTEGRITY-AUTHORITY-001');
            }
            try {
                $authority->assertPatientProvenance($outcome, $runId, $sourceSnapshotId, $targetSnapshotId);
            } catch (\Throwable) {
                throw ProtectedStoreAccessDeniedException::forCode('LM-SEC-PROVENANCE-INTEGRITY-AUTHORITY-001');
            }
            $field = $outcome['field_contract'] ?? '';
            if (! isset($expected[$field]) || ! in_array($outcome['disposition'] ?? '', self::PRIMARY_DISPOSITIONS, true)
                || ($outcome['source_contract'] ?? '') === '' || ($outcome['transformation_version'] ?? '') === ''
                || ($outcome['query_evidence'] ?? '') === '' || ($outcome['result_evidence'] ?? '') === ''
                || ($outcome['root_reference'] ?? '') === '' || ($outcome['subchain_reference'] ?? '') === ''
                || ($outcome['run_id'] ?? null) !== $runId || ($outcome['source_snapshot_id'] ?? null) !== $sourceSnapshotId
                || ($outcome['target_snapshot_id'] ?? null) !== $targetSnapshotId) {
                throw ProtectedStoreAccessDeniedException::forCode('LM-SEC-PROVENANCE-OUTCOME-INVALID-001');
            }
            $expected[$field]++;
        }
        if (array_filter($expected, static fn (int $count): bool => $count !== 1) !== []) {
            throw ProtectedStoreAccessDeniedException::forCode('LM-SEC-PROVENANCE-PRIMARY-CARDINALITY-001');
        }

        $last = [];
        foreach ($secondaryExceptions as $exception) {
            $field = $exception['field_contract'] ?? '';
            $ordinal = $exception['ordinal'] ?? 0;
            if (! isset($expected[$field]) || ! is_int($ordinal) || $ordinal < 1 || ($exception['exception_code'] ?? '') === ''
                || $ordinal <= ($last[$field] ?? 0)) {
                throw ProtectedStoreAccessDeniedException::forCode('LM-SEC-PROVENANCE-SECONDARY-ORDER-001');
            }
            $last[$field] = $ordinal;
        }
    }

    /**
     * @param  list<string>  $consumedInsuranceRowReferences
     * @param  list<array{source_row_reference:string, history_outcome_reference:string, integrity_authority:EnvelopeVerifiedIntegrityAuthority}>  $historyOutcomes
     */
    public function assertInsuranceRowsPreserved(array $consumedInsuranceRowReferences, array $historyOutcomes): void
    {
        $expected = array_fill_keys($consumedInsuranceRowReferences, 0);
        if (count($expected) !== count($consumedInsuranceRowReferences)) {
            throw ProtectedStoreAccessDeniedException::forCode('LM-SEC-PROVENANCE-INSURANCE-ROW-IDENTITY-001');
        }
        $outcomeReferences = [];
        foreach ($historyOutcomes as $outcome) {
            $authority = $outcome['integrity_authority'] ?? null;
            if (! $authority instanceof EnvelopeVerifiedIntegrityAuthority) {
                throw ProtectedStoreAccessDeniedException::forCode('LM-SEC-PROVENANCE-INTEGRITY-AUTHORITY-001');
            }
            try {
                $authority->assertInsuranceProvenance($outcome);
            } catch (\Throwable) {
                throw ProtectedStoreAccessDeniedException::forCode('LM-SEC-PROVENANCE-INTEGRITY-AUTHORITY-001');
            }
            $source = $outcome['source_row_reference'] ?? '';
            $history = $outcome['history_outcome_reference'] ?? '';
            if (! isset($expected[$source]) || $history === '' || isset($outcomeReferences[$history])) {
                throw ProtectedStoreAccessDeniedException::forCode('LM-SEC-PROVENANCE-INSURANCE-OUTCOME-001');
            }
            $expected[$source]++;
            $outcomeReferences[$history] = true;
        }
        if (array_filter($expected, static fn (int $count): bool => $count !== 1) !== []) {
            throw ProtectedStoreAccessDeniedException::forCode('LM-SEC-PROVENANCE-INSURANCE-COMPLETENESS-001');
        }
    }
}
