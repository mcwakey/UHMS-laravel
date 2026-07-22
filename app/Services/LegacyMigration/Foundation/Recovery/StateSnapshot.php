<?php

namespace App\Services\LegacyMigration\Foundation\Recovery;

final readonly class StateSnapshot
{
    public function __construct(
        public string $recordKey,
        public MigrationState $state,
        public int $version,
        public int $attemptCount,
    ) {
        if ($recordKey === '' || $version < 0 || $attemptCount < 0) {
            throw RecoveryException::failClosed('RECOVERY-INVALID-STATE-SNAPSHOT');
        }
    }
}
