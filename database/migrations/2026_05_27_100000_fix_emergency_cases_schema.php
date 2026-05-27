<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // All alterations use raw SQL to avoid Laravel's schema introspection
        // which queries generation_expression (not available in MariaDB 10.1)

        // Rename status -> emergency_status
        if (
            ! $this->columnExists('emergency_cases', 'emergency_status') &&
            $this->columnExists('emergency_cases', 'status')
        ) {
            DB::statement("ALTER TABLE `emergency_cases` CHANGE `status` `emergency_status` VARCHAR(60) NOT NULL DEFAULT 'ARRIVED'");
        }

        // Add admission_id
        if (! $this->columnExists('emergency_cases', 'admission_id')) {
            DB::statement("ALTER TABLE `emergency_cases` ADD COLUMN `admission_id` BIGINT UNSIGNED NULL AFTER `patient_id`");
            DB::statement("ALTER TABLE `emergency_cases` ADD CONSTRAINT `ec_admission_id_foreign` FOREIGN KEY (`admission_id`) REFERENCES `admissions`(`id`) ON DELETE SET NULL");
        }

        // Add emergency_bay_id
        if (! $this->columnExists('emergency_cases', 'emergency_bay_id')) {
            DB::statement("ALTER TABLE `emergency_cases` ADD COLUMN `emergency_bay_id` BIGINT UNSIGNED NULL AFTER `admission_id`");
            DB::statement("ALTER TABLE `emergency_cases` ADD CONSTRAINT `ec_emergency_bay_id_foreign` FOREIGN KEY (`emergency_bay_id`) REFERENCES `emergency_bays`(`id`) ON DELETE SET NULL");
        }

        // Add source
        if (! $this->columnExists('emergency_cases', 'source')) {
            DB::statement("ALTER TABLE `emergency_cases` ADD COLUMN `source` VARCHAR(255) NULL AFTER `brought_by`");
        }

        // Add initial_condition
        if (! $this->columnExists('emergency_cases', 'initial_condition')) {
            DB::statement("ALTER TABLE `emergency_cases` ADD COLUMN `initial_condition` TEXT NULL AFTER `chief_complaint`");
        }

        // Add triage_score
        if (! $this->columnExists('emergency_cases', 'triage_score')) {
            DB::statement("ALTER TABLE `emergency_cases` ADD COLUMN `triage_score` SMALLINT UNSIGNED NULL AFTER `triage_category`");
        }

        // Add triage_notes
        if (! $this->columnExists('emergency_cases', 'triage_notes')) {
            DB::statement("ALTER TABLE `emergency_cases` ADD COLUMN `triage_notes` TEXT NULL AFTER `triage_score`");
        }

        // Add created_by
        if (! $this->columnExists('emergency_cases', 'created_by')) {
            DB::statement("ALTER TABLE `emergency_cases` ADD COLUMN `created_by` BIGINT UNSIGNED NULL AFTER `assigned_nurse_id`");
            DB::statement("ALTER TABLE `emergency_cases` ADD CONSTRAINT `ec_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE SET NULL");
        }

        // Add disposition_notes
        if (! $this->columnExists('emergency_cases', 'disposition_notes')) {
            DB::statement("ALTER TABLE `emergency_cases` ADD COLUMN `disposition_notes` TEXT NULL AFTER `disposition`");
        }

        // Add disposition_time
        if (! $this->columnExists('emergency_cases', 'disposition_time')) {
            DB::statement("ALTER TABLE `emergency_cases` ADD COLUMN `disposition_time` DATETIME NULL AFTER `disposition_notes`");
        }

        // Add disposed_by
        if (! $this->columnExists('emergency_cases', 'disposed_by')) {
            DB::statement("ALTER TABLE `emergency_cases` ADD COLUMN `disposed_by` BIGINT UNSIGNED NULL AFTER `disposition_time`");
            DB::statement("ALTER TABLE `emergency_cases` ADD CONSTRAINT `ec_disposed_by_foreign` FOREIGN KEY (`disposed_by`) REFERENCES `users`(`id`) ON DELETE SET NULL");
        }
    }

    public function down(): void
    {
        if ($this->columnExists('emergency_cases', 'emergency_status')) {
            DB::statement("ALTER TABLE `emergency_cases` CHANGE `emergency_status` `status` VARCHAR(60) NOT NULL DEFAULT 'ARRIVED'");
        }

        if ($this->columnExists('emergency_cases', 'disposed_by')) {
            DB::statement("ALTER TABLE `emergency_cases` DROP FOREIGN KEY `ec_disposed_by_foreign`");
            DB::statement("ALTER TABLE `emergency_cases` DROP COLUMN `disposed_by`");
        }

        if ($this->columnExists('emergency_cases', 'disposition_time')) {
            DB::statement("ALTER TABLE `emergency_cases` DROP COLUMN `disposition_time`");
        }

        if ($this->columnExists('emergency_cases', 'disposition_notes')) {
            DB::statement("ALTER TABLE `emergency_cases` DROP COLUMN `disposition_notes`");
        }

        if ($this->columnExists('emergency_cases', 'created_by')) {
            DB::statement("ALTER TABLE `emergency_cases` DROP FOREIGN KEY `ec_created_by_foreign`");
            DB::statement("ALTER TABLE `emergency_cases` DROP COLUMN `created_by`");
        }

        if ($this->columnExists('emergency_cases', 'triage_notes')) {
            DB::statement("ALTER TABLE `emergency_cases` DROP COLUMN `triage_notes`");
        }

        if ($this->columnExists('emergency_cases', 'triage_score')) {
            DB::statement("ALTER TABLE `emergency_cases` DROP COLUMN `triage_score`");
        }

        if ($this->columnExists('emergency_cases', 'initial_condition')) {
            DB::statement("ALTER TABLE `emergency_cases` DROP COLUMN `initial_condition`");
        }

        if ($this->columnExists('emergency_cases', 'source')) {
            DB::statement("ALTER TABLE `emergency_cases` DROP COLUMN `source`");
        }

        if ($this->columnExists('emergency_cases', 'emergency_bay_id')) {
            DB::statement("ALTER TABLE `emergency_cases` DROP FOREIGN KEY `ec_emergency_bay_id_foreign`");
            DB::statement("ALTER TABLE `emergency_cases` DROP COLUMN `emergency_bay_id`");
        }

        if ($this->columnExists('emergency_cases', 'admission_id')) {
            DB::statement("ALTER TABLE `emergency_cases` DROP FOREIGN KEY `ec_admission_id_foreign`");
            DB::statement("ALTER TABLE `emergency_cases` DROP COLUMN `admission_id`");
        }
    }

    private function columnExists(string $table, string $column): bool
    {
        if (DB::getDriverName() === 'sqlite') {
            return Schema::hasColumn($table, $column);
        }

        $table = str_replace('`', '``', $table);
        $column = str_replace("'", "''", $column);

        return ! empty(DB::select("SHOW COLUMNS FROM `{$table}` LIKE '{$column}'"));
    }
};
