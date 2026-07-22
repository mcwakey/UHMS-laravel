<?php

namespace App\Services\LegacyMigration\Foundation\Recovery;

final readonly class AtomicIntentDescriptor
{
    public function __construct(
        public string $intentToken,
        public string $idempotencyToken,
        public AtomicUnit $unit,
        public MigrationState $expectedPriorState,
        public int $attempt,
        public string $inputFingerprint,
    ) {
        foreach ([$intentToken, $idempotencyToken, $inputFingerprint] as $token) {
            if (preg_match('/\A[0-9a-f]{64}\z/D', $token) !== 1) {
                throw RecoveryException::failClosed('RECOVERY-INVALID-INTENT');
            }
        }
        if ($attempt < 1) {
            throw RecoveryException::failClosed('RECOVERY-INVALID-ATTEMPT');
        }
    }
}
