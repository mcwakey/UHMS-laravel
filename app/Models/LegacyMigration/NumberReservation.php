<?php

namespace App\Models\LegacyMigration;

class NumberReservation extends ProtectedFoundationModel
{
    protected $table = 'legacy_migration_number_reservations';

    protected function casts(): array
    {
        return parent::casts() + [
            'encrypted_number_payload' => 'encrypted:array',
            'encrypted_explanation' => 'encrypted:array',
        ];
    }
}
