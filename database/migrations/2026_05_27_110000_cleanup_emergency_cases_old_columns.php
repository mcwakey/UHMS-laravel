<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Drop FK constraints on old columns first
        $fksToDrop = [
            'emergency_cases_registered_by_foreign'   => 'registered_by',
            'emergency_cases_treatment_area_id_foreign' => 'treatment_area_id',
            'emergency_cases_disposition_by_foreign'  => 'disposition_by',
            'emergency_cases_certified_by_foreign'    => 'certified_by',
        ];

        foreach ($fksToDrop as $fkName => $column) {
            if (! empty(DB::select("SHOW COLUMNS FROM `emergency_cases` LIKE '{$column}'"))) {
                DB::statement("ALTER TABLE `emergency_cases` DROP FOREIGN KEY `{$fkName}`");
                DB::statement("ALTER TABLE `emergency_cases` DROP COLUMN `{$column}`");
            }
        }

        // Drop remaining old columns (no FK constraints)
        $columnsToDrop = [
            'notes',            // conflicts with notes() relationship
            'accompanied_by',
            'referral_source',
            'disposition_at',
            'discharge_summary',
            'referral_reason',
            'death_time',
            'death_cause',
            'deleted_at',
        ];

        foreach ($columnsToDrop as $column) {
            if (! empty(DB::select("SHOW COLUMNS FROM `emergency_cases` LIKE '{$column}'"))) {
                DB::statement("ALTER TABLE `emergency_cases` DROP COLUMN `{$column}`");
            }
        }
    }

    public function down(): void
    {
        if (empty(DB::select("SHOW COLUMNS FROM `emergency_cases` LIKE 'notes'"))) {
            DB::statement("ALTER TABLE `emergency_cases` ADD COLUMN `notes` TEXT NULL");
        }
    }
};
