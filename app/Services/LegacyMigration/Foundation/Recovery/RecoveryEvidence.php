<?php

namespace App\Services\LegacyMigration\Foundation\Recovery;

final readonly class RecoveryEvidence
{
    private function __construct(
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

    /** @param array<string,bool> $facts */
    public static function fromProtectedStore(ProtectedRecoveryStore $issuer, array $facts): self
    {
        return self::fromFacts($facts);
    }

    /** @param array<string,bool> $facts */
    public static function syntheticForTests(array $facts): self
    {
        if (! defined('PHPUNIT_COMPOSER_INSTALL') && ! defined('__PHPUNIT_PHAR__')) {
            throw RecoveryException::failClosed('RECOVERY-SYNTHETIC-EVIDENCE-FORBIDDEN');
        }

        return self::fromFacts($facts);
    }

    /** @return array<string,bool> */
    public function toArray(): array
    {
        return get_object_vars($this);
    }

    /** @param array<string,bool> $facts */
    private static function fromFacts(array $facts): self
    {
        $required = [
            'coordinatesMatch', 'lineageCompatible', 'unexpectedDurableFacts',
            'durableUnitCommitted', 'durableFactsComplete', 'checkpointPresent',
            'mandatoryReconciliationPresent', 'reconciliationPassed',
            'transactionRolledBack', 'allocationConsumptionExplained',
        ];
        if (array_keys($facts) !== $required) {
            throw RecoveryException::failClosed('RECOVERY-EVIDENCE-SHAPE-INVALID');
        }
        foreach ($facts as $value) {
            if (! is_bool($value)) {
                throw RecoveryException::failClosed('RECOVERY-EVIDENCE-SHAPE-INVALID');
            }
        }

        return new self(...array_values($facts));
    }
}
