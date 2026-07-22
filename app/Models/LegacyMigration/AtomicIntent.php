<?php

namespace App\Models\LegacyMigration;

class AtomicIntent extends ProtectedFoundationModel
{
    protected $table = 'legacy_migration_atomic_intents';

    protected function casts(): array
    {
        return parent::casts() + ['encrypted_write_set_refs' => 'encrypted:array'];
    }
}
