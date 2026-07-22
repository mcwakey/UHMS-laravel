<?php

namespace App\Models\LegacyMigration;

use App\Models\LegacyMigration\Concerns\AppendOnly;

class QuarantineException extends ProtectedFoundationModel
{
    use AppendOnly;

    protected $table = 'legacy_migration_quarantine_exceptions';

    protected function casts(): array
    {
        return parent::casts() + ['encrypted_evidence' => 'encrypted:array'];
    }
}
