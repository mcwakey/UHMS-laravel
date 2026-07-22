<?php

namespace App\Models\LegacyMigration;

use App\Models\LegacyMigration\Concerns\AppendOnly;

class ProtectedPurgeRequest extends ProtectedFoundationModel
{
    use AppendOnly;

    protected $table = 'legacy_migration_protected_purge_requests';

    protected $hidden = [
        'requested_by_authority_reference', 'authorized_by_authority_reference',
        'aggregate_tombstone_hash', 'integrity_checksum',
    ];

    protected function casts(): array
    {
        return parent::casts() + [
            'integrity_verified' => 'boolean',
            'lineage_preservation_verified' => 'boolean',
            'tombstone_required' => 'boolean',
            'requested_at' => 'immutable_datetime',
            'authorized_at' => 'immutable_datetime',
            'executed_at' => 'immutable_datetime',
        ];
    }
}
