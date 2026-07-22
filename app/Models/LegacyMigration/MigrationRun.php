<?php

namespace App\Models\LegacyMigration;

class MigrationRun extends ProtectedFoundationModel
{
    public const STATES = [
        'NOT_STARTED', 'EXTRACTED', 'CLASSIFIED', 'DRY_RUN_ACCEPTED',
        'CORE_COMMITTING', 'CORE_COMMITTED', 'ALIAS_PENDING',
        'ALIAS_COMMITTED', 'ALIAS_WITHHELD', 'CONTACT_PENDING',
        'CONTACT_COMMITTED', 'CONTACT_WITHHELD', 'INSURANCE_HISTORY_PENDING',
        'INSURANCE_HISTORY_COMMITTED', 'INSURANCE_CURRENT_PENDING',
        'INSURANCE_CURRENT_COMMITTED', 'INSURANCE_CURRENT_WITHHELD',
        'RECONCILIATION_PENDING', 'RECONCILIATION_PASSED',
        'RECONCILIATION_FAILED', 'QUARANTINED', 'ROLLBACK_REQUIRED',
        'COMPENSATION_REQUIRED', 'COMPLETED',
    ];

    protected $table = 'legacy_migration_runs';

    protected function casts(): array
    {
        return parent::casts() + ['encrypted_manifest' => 'encrypted:array'];
    }
}
