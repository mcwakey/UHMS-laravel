<?php

namespace App\Models\LegacyMigration;

use App\Models\LegacyMigration\Concerns\AppendOnly;

class Checkpoint extends ProtectedFoundationModel
{
    use AppendOnly;

    protected $table = 'legacy_migration_checkpoints';

    protected function casts(): array
    {
        return parent::casts() + ['encrypted_durable_fact_refs' => 'encrypted:array'];
    }
}
