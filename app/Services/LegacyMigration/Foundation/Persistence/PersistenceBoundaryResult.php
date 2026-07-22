<?php

namespace App\Services\LegacyMigration\Foundation\Persistence;

final readonly class PersistenceBoundaryResult
{
    public function __construct(
        public PersistenceOperation $operation,
        public string $outcome,
        public int $businessDomainWrites,
        public bool $nonbinding,
    ) {
        if ($businessDomainWrites < 0) {
            throw new PersistenceBoundaryException('FOUNDATION_PERSISTENCE_RESULT_INVALID', 'A persistence result is invalid.');
        }
    }
}
