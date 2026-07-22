<?php

namespace App\Models\LegacyMigration;

class Remediation extends ProtectedFoundationModel
{
    protected $table = 'legacy_migration_remediations';

    protected function casts(): array
    {
        return parent::casts() + [
            'encrypted_values' => 'encrypted:array',
            'encrypted_evidence_metadata' => 'encrypted:array',
        ];
    }
}
