<?php

namespace App\Models\LegacyMigration;

use App\Models\LegacyMigration\Concerns\AppendOnly;

class ProtectedRetentionPolicy extends ProtectedFoundationModel
{
    use AppendOnly;

    protected $table = 'legacy_migration_retention_policies';

    protected $hidden = ['purge_authority_reference', 'owner_approval_reference', 'integrity_checksum'];

    protected function casts(): array
    {
        return parent::casts() + [
            'legal_hold' => 'boolean',
            'operational_hold' => 'boolean',
            'purge_enabled' => 'boolean',
            'retain_tombstone' => 'boolean',
            'active' => 'boolean',
            'review_at' => 'immutable_datetime',
        ];
    }
}
