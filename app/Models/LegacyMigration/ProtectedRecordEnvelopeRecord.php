<?php

namespace App\Models\LegacyMigration;

use App\Models\LegacyMigration\Concerns\AppendOnly;

class ProtectedRecordEnvelopeRecord extends ProtectedFoundationModel
{
    use AppendOnly;

    protected $table = 'legacy_migration_protected_record_envelopes';

    protected $hidden = [
        'protected_token', 'encrypted_token_envelope', 'encrypted_token_set', 'encrypted_integrity_seal',
        'hmac_key_id', 'hmac_key_version', 'canonicalization_version',
    ];

    protected function casts(): array
    {
        return parent::casts() + [
            'encrypted_token_envelope' => 'encrypted',
            'encrypted_token_set' => 'encrypted:array',
            'encrypted_integrity_seal' => 'encrypted',
            'sealed_at' => 'immutable_datetime',
        ];
    }
}
