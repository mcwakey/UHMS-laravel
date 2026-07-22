<?php

namespace App\Models\LegacyMigration;

use App\Models\LegacyMigration\Concerns\AppendOnly;

class ProvenanceRecord extends ProtectedFoundationModel
{
    use AppendOnly;

    protected $table = 'legacy_migration_provenance_records';

    protected function casts(): array
    {
        return parent::casts() + [
            'encrypted_raw_payload' => 'encrypted:array',
            'encrypted_field_dispositions' => 'encrypted:array',
            'encrypted_outcome_metadata' => 'encrypted:array',
        ];
    }
}
