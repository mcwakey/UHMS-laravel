<?php

namespace App\Models\LegacyMigration;

use App\Models\LegacyMigration\Concerns\AppendOnly;

class ReconciliationResult extends ProtectedFoundationModel
{
    use AppendOnly;

    protected $table = 'legacy_migration_reconciliation_results';

    protected function casts(): array
    {
        return parent::casts() + [
            'difference' => 'decimal:8',
            'tolerance' => 'decimal:8',
            'encrypted_population_definition' => 'encrypted:array',
            'encrypted_expected_equation' => 'encrypted:array',
            'encrypted_measured_values' => 'encrypted:array',
            'encrypted_exception_refs' => 'encrypted:array',
        ];
    }
}
