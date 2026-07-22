<?php

namespace App\Models\LegacyMigration;

class QuarantineRoot extends ProtectedFoundationModel
{
    protected $table = 'legacy_migration_quarantine_roots';

    protected function casts(): array
    {
        return parent::casts() + [
            'encrypted_release_conditions' => 'encrypted:array',
            'encrypted_dependency_edges' => 'encrypted:array',
        ];
    }
}
