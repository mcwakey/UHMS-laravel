<?php

namespace App\Services\LegacyMigration\Foundation\Recovery;

final readonly class RecoveryCheckpoint
{
    public function __construct(
        public string $stage,
        public string $transactionEvidenceHash,
        public string $writeSetHash,
        public string $reconciliationBundleHash,
    ) {
        if ($stage === '') {
            throw RecoveryException::failClosed('RECOVERY-INVALID-CHECKPOINT');
        }
        foreach ([$transactionEvidenceHash, $writeSetHash, $reconciliationBundleHash] as $hash) {
            if (preg_match('/\A[0-9a-f]{64}\z/D', $hash) !== 1) {
                throw RecoveryException::failClosed('RECOVERY-INVALID-CHECKPOINT');
            }
        }
    }
}
