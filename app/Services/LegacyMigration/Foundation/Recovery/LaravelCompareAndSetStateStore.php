<?php

namespace App\Services\LegacyMigration\Foundation\Recovery;

/** Protected compare-and-set over one durable atomic-intent coordinate. */
final class LaravelCompareAndSetStateStore implements CompareAndSetStateStore
{
    public function __construct(private readonly ProtectedRecoveryStore $store) {}

    public function compareAndSet(
        string $recordKey,
        MigrationState $expectedState,
        int $expectedVersion,
        int $expectedAttemptCount,
        MigrationState $nextState,
        int $nextAttemptCount,
    ): ?StateSnapshot {
        MonotonicStateMachine::assertTransitionAllowed($expectedState, $nextState);

        return $this->store->compareAndSet(
            $recordKey,
            $expectedState,
            $expectedVersion,
            $expectedAttemptCount,
            $nextState,
            $nextAttemptCount,
        );
    }
}
