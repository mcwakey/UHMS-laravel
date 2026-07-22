<?php

namespace App\Services\LegacyMigration\Foundation\Storage;

final class MigrationStateTransitions
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

    public static function assertAllowed(string $from, string $to): void
    {
        if (! array_key_exists($from, self::NEXT) || ! in_array($to, self::NEXT[$from], true)) {
            throw new StorageIntegrityException("Invalid migration state transition from {$from} to {$to}.");
        }
    }

    /** @return list<string> */
    public static function states(): array
    {
        return array_keys(self::NEXT);
    }
}
