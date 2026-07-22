<?php

namespace App\Models\LegacyMigration;

class Crosswalk extends ProtectedFoundationModel
{
    protected $table = 'legacy_migration_crosswalks';

    protected function casts(): array
    {
        return parent::casts() + [
            'encrypted_mapping_payload' => 'encrypted:array',
            'encrypted_authoritative_rule_ids' => 'encrypted:array',
        ];
    }
}
