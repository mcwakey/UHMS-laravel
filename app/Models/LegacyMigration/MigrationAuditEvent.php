<?php

namespace App\Models\LegacyMigration;

use App\Models\LegacyMigration\Concerns\AppendOnly;

class MigrationAuditEvent extends ProtectedFoundationModel
{
    use AppendOnly;

    public const UPDATED_AT = null;

    protected $table = 'legacy_migration_audit_events';

    protected function casts(): array
    {
        return parent::casts() + ['encrypted_event_payload' => 'encrypted:array'];
    }
}
