<?php

namespace App\Services\LegacyMigration\Foundation\Recovery;

final readonly class RecoveryObservation
{
    private function __construct(
        public CrashBoundary $boundary,
        public RecoveryEvidence $evidence,
        public ?RecoveryCheckpoint $checkpointRepair,
        public string $durableEvidenceHash,
    ) {
        if (preg_match('/\A[a-f0-9]{64}\z/D', $durableEvidenceHash) !== 1) {
            throw RecoveryException::failClosed('RECOVERY-OBSERVATION-HASH-INVALID');
        }
    }

    public static function fromProtectedStore(
        ProtectedRecoveryStore $issuer,
        CrashBoundary $boundary,
        RecoveryEvidence $evidence,
        ?RecoveryCheckpoint $checkpointRepair,
        string $durableEvidenceHash,
    ): self {
        return new self($boundary, $evidence, $checkpointRepair, $durableEvidenceHash);
    }

    public static function syntheticForTests(
        CrashBoundary $boundary,
        RecoveryEvidence $evidence,
        ?RecoveryCheckpoint $checkpointRepair = null,
    ): self {
        if (! defined('PHPUNIT_COMPOSER_INSTALL') && ! defined('__PHPUNIT_PHAR__')) {
            throw RecoveryException::failClosed('RECOVERY-SYNTHETIC-OBSERVATION-FORBIDDEN');
        }

        return new self($boundary, $evidence, $checkpointRepair, hash('sha256', json_encode([
            'boundary' => $boundary->value,
            'evidence' => $evidence->toArray(),
            'checkpoint_repair' => $checkpointRepair === null ? null : [
                $checkpointRepair->stage,
                $checkpointRepair->transactionEvidenceHash,
                $checkpointRepair->writeSetHash,
                $checkpointRepair->reconciliationBundleHash,
            ],
        ], JSON_THROW_ON_ERROR)));
    }
}
