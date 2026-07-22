<?php

namespace App\Services\LegacyMigration\Foundation\Recovery;

interface CompareAndSetStateStore
{
    /** Return null when expected state/version/attempt no longer matches. */
    public function compareAndSet(
        string $recordKey,
        MigrationState $expectedState,
        int $expectedVersion,
        int $expectedAttemptCount,
        MigrationState $nextState,
        int $nextAttemptCount,
    ): ?StateSnapshot;
}
