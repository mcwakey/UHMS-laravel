<?php

namespace Tests\Unit\LegacyMigration\Foundation\Security;

use App\Services\LegacyMigration\Foundation\Security\ProvenanceCompletenessValidator;
use App\Services\LegacyMigration\Foundation\Security\VerifiedIntegrityAuthority;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class ProvenanceCompletenessValidatorTest extends TestCase
{
    #[Test]
    public function malicious_noop_integrity_interface_cannot_claim_provenance_integrity(): void
    {
        $forged = new class implements VerifiedIntegrityAuthority
        {
            public function assertVerified(): void {}
        };
        $this->expectExceptionMessage('LM-SEC-PROVENANCE-INTEGRITY-AUTHORITY-001');
        (new ProvenanceCompletenessValidator)->assertInsuranceRowsPreserved(
            ['SOURCE-ROW-101'],
            [['source_row_reference' => 'SOURCE-ROW-101', 'history_outcome_reference' => 'HISTORY-101', 'integrity_authority' => $forged]],
        );
    }

    #[Test]
    public function a_caller_boolean_cannot_claim_provenance_integrity(): void
    {
        $this->expectExceptionMessage('LM-SEC-PROVENANCE-INTEGRITY-AUTHORITY-001');
        (new ProvenanceCompletenessValidator)->assertPatientFieldsComplete(
            ['patient.dob'],
            [[
                'field_contract' => 'patient.dob', 'disposition' => 'mapped',
                'source_contract' => 'P2F-SOURCE-1', 'transformation_version' => 'P2F-TRANSFORM-1',
                'query_evidence' => str_repeat('a', 64), 'result_evidence' => str_repeat('b', 64),
                'root_reference' => 'ROOT-1', 'subchain_reference' => 'CHAIN-1',
                'run_id' => 1, 'source_snapshot_id' => 2, 'target_snapshot_id' => 3,
                'integrity_verified' => true,
            ]],
            [],
            1, 2, 3,
        );
    }
}
