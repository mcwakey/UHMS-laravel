<?php

namespace App\Services\LegacyMigration\Foundation\Recovery;

final readonly class AtomicIntentSnapshot
{
    public function __construct(
        public AtomicIntentDescriptor $descriptor,
        public MigrationState $state,
        public int $lockVersion,
    ) {
        if ($lockVersion < 0) {
            throw RecoveryException::failClosed('RECOVERY-INVALID-INTENT-SNAPSHOT');
        }
    }
}
