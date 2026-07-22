<?php

namespace App\Services\LegacyMigration\Foundation\Recovery;

final readonly class RecoveryEvidence
{
    public function __construct(
        public bool $coordinatesMatch,
        public bool $lineageCompatible,
        public bool $unexpectedDurableFacts,
        public bool $durableUnitCommitted,
        public bool $durableFactsComplete,
        public bool $checkpointPresent,
        public bool $mandatoryReconciliationPresent,
        public bool $reconciliationPassed,
        public bool $transactionRolledBack,
        public bool $allocationConsumptionExplained,
    ) {
        if (($this->transactionRolledBack && $this->durableUnitCommitted)
            || ($this->durableFactsComplete && ! $this->durableUnitCommitted)
            || ($this->reconciliationPassed && ! $this->mandatoryReconciliationPresent)) {
            throw RecoveryException::failClosed('RECOVERY-CONTRADICTORY-EVIDENCE');
        }
    }

    public static function cleanRetry(): self
    {
        return new self(true, true, false, false, false, false, false, false, false, true);
    }
}
