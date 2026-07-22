<?php

namespace App\Services\LegacyMigration\Foundation\Allocation;

final readonly class SequenceConsumption
{
    public function __construct(
        public string $lineageToken,
        public int $sequenceOrdinal,
        public string $classification,
    ) {
        if (preg_match('/\A[0-9a-f]{64}\z/D', $lineageToken) !== 1
            || $sequenceOrdinal < 1
            || ! in_array($classification, ['committed', 'transactionally_released', 'explained'], true)) {
            throw AllocationException::failClosed('PATIENT-NUM-UNCLASSIFIED-CONSUMPTION');
        }
    }
}
