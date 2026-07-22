<?php

namespace App\Models\LegacyMigration;

use App\Models\LegacyMigration\Concerns\AppendOnly;

class ContractBundle extends ProtectedFoundationModel
{
    use AppendOnly;

    protected $table = 'legacy_migration_contract_bundles';

    protected function casts(): array
    {
        return parent::casts() + ['encrypted_manifest' => 'encrypted:array'];
    }
}
