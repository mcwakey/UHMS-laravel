<?php

namespace App\Models\LegacyMigration;

use App\Models\LegacyMigration\Concerns\AppendOnly;

class Snapshot extends ProtectedFoundationModel
{
    use AppendOnly;

    protected $table = 'legacy_migration_snapshots';

    protected function casts(): array
    {
        return parent::casts() + ['encrypted_metadata' => 'encrypted:array'];
    }
}
