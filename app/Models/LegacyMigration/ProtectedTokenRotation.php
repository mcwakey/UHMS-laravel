<?php

namespace App\Models\LegacyMigration;

use App\Models\LegacyMigration\Concerns\AppendOnly;

class ProtectedTokenRotation extends ProtectedFoundationModel
{
    use AppendOnly;

    protected $table = 'legacy_migration_protected_token_rotations';

    protected $hidden = ['old_hmac_key_id', 'new_hmac_key_id', 'integrity_checksum'];

    protected function casts(): array
    {
        return parent::casts() + [
            'old_token_verified' => 'boolean',
            'new_token_verified' => 'boolean',
            'rotated_at' => 'immutable_datetime',
        ];
    }
}
