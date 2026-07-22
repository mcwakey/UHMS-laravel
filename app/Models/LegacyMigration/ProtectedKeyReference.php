<?php

namespace App\Models\LegacyMigration;

use App\Models\LegacyMigration\Concerns\AppendOnly;

class ProtectedKeyReference extends ProtectedFoundationModel
{
    use AppendOnly;

    protected $table = 'legacy_migration_protected_key_references';

    protected $hidden = ['secret_reference', 'integrity_checksum'];

    protected function casts(): array
    {
        return parent::casts() + [
            'revoked' => 'boolean',
            'active_for_signing' => 'boolean',
            'activated_at' => 'immutable_datetime',
            'retired_at' => 'immutable_datetime',
            'verification_expires_at' => 'immutable_datetime',
        ];
    }
}
