<?php

namespace App\Services\LegacyMigration\Foundation\Recovery;

final readonly class MonotonicStateMachine
{
    /** @var array<string, list<string>> */
    private const NEXT = [
        'NOT_STARTED' => ['EXTRACTED', 'QUARANTINED'],
        'EXTRACTED' => ['CLASSIFIED', 'QUARANTINED'],
        'CLASSIFIED' => ['DRY_RUN_ACCEPTED', 'QUARANTINED'],
        'DRY_RUN_ACCEPTED' => ['CORE_COMMITTING', 'QUARANTINED'],
        'CORE_COMMITTING' => ['CORE_COMMITTED', 'ROLLBACK_REQUIRED', 'COMPENSATION_REQUIRED'],
        'CORE_COMMITTED' => ['ALIAS_PENDING', 'COMPENSATION_REQUIRED'],
        'ALIAS_PENDING' => ['ALIAS_COMMITTED', 'ALIAS_WITHHELD', 'ROLLBACK_REQUIRED', 'COMPENSATION_REQUIRED'],
        'ALIAS_COMMITTED' => ['CONTACT_PENDING', 'COMPENSATION_REQUIRED'],
        'ALIAS_WITHHELD' => ['CONTACT_PENDING'],
        'CONTACT_PENDING' => ['CONTACT_COMMITTED', 'CONTACT_WITHHELD', 'ROLLBACK_REQUIRED', 'COMPENSATION_REQUIRED'],
        'CONTACT_COMMITTED' => ['INSURANCE_HISTORY_PENDING', 'COMPENSATION_REQUIRED'],
        'CONTACT_WITHHELD' => ['INSURANCE_HISTORY_PENDING'],
        'INSURANCE_HISTORY_PENDING' => ['INSURANCE_HISTORY_COMMITTED', 'ROLLBACK_REQUIRED', 'COMPENSATION_REQUIRED'],
        'INSURANCE_HISTORY_COMMITTED' => ['INSURANCE_CURRENT_PENDING', 'COMPENSATION_REQUIRED'],
        'INSURANCE_CURRENT_PENDING' => ['INSURANCE_CURRENT_COMMITTED', 'INSURANCE_CURRENT_WITHHELD', 'ROLLBACK_REQUIRED', 'COMPENSATION_REQUIRED'],
        'INSURANCE_CURRENT_COMMITTED' => ['RECONCILIATION_PENDING', 'COMPENSATION_REQUIRED'],
        'INSURANCE_CURRENT_WITHHELD' => ['RECONCILIATION_PENDING'],
        'RECONCILIATION_PENDING' => ['RECONCILIATION_PASSED', 'RECONCILIATION_FAILED', 'COMPENSATION_REQUIRED'],
        'RECONCILIATION_PASSED' => ['COMPLETED'],
        'RECONCILIATION_FAILED' => [],
        'QUARANTINED' => [],
        'ROLLBACK_REQUIRED' => [],
        'COMPENSATION_REQUIRED' => [],
        'COMPLETED' => [],
    ];

    public function __construct(private CompareAndSetStateStore $store) {}

    /** @return list<string> */
    public static function states(): array
    {
        return array_keys(self::NEXT);
    }

    public function transition(StateSnapshot $expected, MigrationState $next): StateSnapshot
    {
        self::assertTransitionAllowed($expected->state, $next);

        $updated = $this->store->compareAndSet(
            $expected->recordKey,
            $expected->state,
            $expected->version,
            $expected->attemptCount,
            $next,
            $expected->attemptCount + 1,
        );

        if ($updated === null
            || $updated->state !== $next
            || $updated->version !== $expected->version + 1
            || $updated->attemptCount !== $expected->attemptCount + 1) {
            throw RecoveryException::failClosed('RECOVERY-CAS-CONFLICT');
        }

        return $updated;
    }

    public static function assertTransitionAllowed(MigrationState $expected, MigrationState $next): void
    {
        if (! in_array($next->value, self::NEXT[$expected->value] ?? [], true)) {
            throw RecoveryException::failClosed('RECOVERY-INVALID-TRANSITION');
        }
    }
}
