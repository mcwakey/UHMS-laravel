<?php

namespace App\Models\LegacyMigration;

use App\Models\LegacyMigration\Concerns\AppendOnly;

class ProtectedAccessAudit extends ProtectedFoundationModel
{
    use AppendOnly;

    protected $table = 'legacy_migration_protected_access_audits';

    protected $hidden = ['authority_reference', 'event_checksum'];

    protected function casts(): array
    {
        return parent::casts() + ['occurred_at' => 'immutable_datetime'];
    }
}
