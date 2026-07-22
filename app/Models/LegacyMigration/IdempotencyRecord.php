<?php

namespace App\Models\LegacyMigration;

class IdempotencyRecord extends ProtectedFoundationModel
{
    protected $table = 'legacy_migration_idempotency_records';

    protected function casts(): array
    {
        return parent::casts() + ['encrypted_outcome_payload' => 'encrypted:array'];
    }
}
