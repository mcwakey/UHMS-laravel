<?php

namespace App\Services\LegacyMigration\Foundation\Allocation;

final readonly class SequenceReconciliation
{
    public function __construct(
        public int $sequenceDelta,
        public int $committed,
        public int $operationalCommitted,
        public int $transactionallyReleased,
        public int $explained,
        public int $difference,
        public bool $passed,
    ) {}
}
