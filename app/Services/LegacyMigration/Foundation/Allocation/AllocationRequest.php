<?php

namespace App\Services\LegacyMigration\Foundation\Allocation;

final readonly class AllocationRequest
{
    public function __construct(
        public string $protectedSourceToken,
        public string $patientCoreKey,
        public PinnedNumberingConfiguration $configuration,
        public AllocationMode $mode,
    ) {
        foreach ([$protectedSourceToken, $patientCoreKey] as $token) {
            if (preg_match('/\A[0-9a-f]{64}\z/D', $token) !== 1) {
                throw AllocationException::failClosed('PATIENT-NUM-INVALID-LINEAGE');
            }
        }
    }
}
