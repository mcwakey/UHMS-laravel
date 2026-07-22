<?php

namespace App\Models\LegacyMigration;

use App\Models\LegacyMigration\Concerns\AppendOnly;

class CompensationRecord extends ProtectedFoundationModel
{
    use AppendOnly;

    public const UPDATED_AT = null;

    protected $table = 'legacy_migration_compensation_records';

    protected function casts(): array
    {
        return parent::casts() + ['encrypted_action_payload' => 'encrypted:array'];
    }
}
