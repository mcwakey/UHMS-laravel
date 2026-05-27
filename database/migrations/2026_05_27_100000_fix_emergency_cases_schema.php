<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // All alterations use raw SQL to avoid Laravel's schema introspection
        // which queries generation_expression (not available in MariaDB 10.1)

        // Rename status -> emergency_status
        if (
            empty(DB::select("SHOW COLUMNS FROM `emergency_cases` LIKE 'emergency_status'")) &&
            ! empty(DB::select("SHOW COLUMNS FROM `emergency_cases` LIKE 'status'"))
        ) {
            DB::statement("ALTER TABLE `emergency_cases` CHANGE `status` `emergency_status` VARCHAR(60) NOT NULL DEFAULT 'ARRIVED'");
        }

        // Add admission_id
        if (empty(DB::select("SHOW COLUMNS FROM `emergency_cases` LIKE 'admission_id'"))) {
            DB::statement("ALTER TABLE `emergency_cases` ADD COLUMN `admission_id` BIGINT UNSIGNED NULL AFTER `patient_id`");
            DB::statement("ALTER TABLE `emergency_cases` ADD CONSTRAINT `ec_admission_id_foreign` FOREIGN KEY (`admission_id`) REFERENCES `admissions`(`id`) ON DELETE SET NULL");
        }

        // Add emergency_bay_id
        if (empty(DB::select("SHOW COLUMNS FROM `emergency_cases` LIKE 'emergency_bay_id'"))) {
            DB::statement("ALTER TABLE `emergency_cases` ADD COLUMN `emergency_bay_id` BIGINT UNSIGNED NULL AFTER `admission_id`");
            DB::statement("ALTER TABLE `emergency_cases` ADD CONSTRAINT `ec_emergency_bay_id_foreign` FOREIGN KEY (`emergency_bay_id`) REFERENCES `emergency_bays`(`id`) ON DELETE SET NULL");
        }

        // Add source
        if (empty(DB::select("SHOW COLUMNS FROM `emergency_cases` LIKE 'source'"))) {
            DB::statement("ALTER TABLE `emergency_cases` ADD COLUMN `source` VARCHAR(255) NULL AFTER `brought_by`");
        }

        // Add initial_condition
        if (empty(DB::select("SHOW COLUMNS FROM `emergency_cases` LIKE 'initial_condition'"))) {
            DB::statement("ALTER TABLE `emergency_cases` ADD COLUMN `initial_condition` TEXT NULL AFTER `chief_complaint`");
        }

        // Add triage_score
        if (empty(DB::select("SHOW COLUMNS FROM `emergency_cases` LIKE 'triage_score'"))) {
            DB::statement("ALTER TABLE `emergency_cases` ADD COLUMN `triage_score` SMALLINT UNSIGNED NULL AFTER `triage_category`");
        }

        // Add triage_notes
        if (empty(DB::select("SHOW COLUMNS FROM `emergency_cases` LIKE 'triage_notes'"))) {
            DB::statement("ALTER TABLE `emergency_cases` ADD COLUMN `triage_notes` TEXT NULL AFTER `triage_score`");
        }

        // Add created_by
        if (empty(DB::select("SHOW COLUMNS FROM `emergency_cases` LIKE 'created_by'"))) {
            DB::statement("ALTER TABLE `emergency_cases` ADD COLUMN `created_by` BIGINT UNSIGNED NULL AFTER `assigned_nurse_id`");
            DB::statement("ALTER TABLE `emergency_cases` ADD CONSTRAINT `ec_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE SET NULL");
        }

        // Add disposition_notes
        if (empty(DB::select("SHOW COLUMNS FROM `emergency_cases` LIKE 'disposition_notes'"))) {
            DB::statement("ALTER TABLE `emergency_cases` ADD COLUMN `disposition_notes` TEXT NULL AFTER `disposition`");
        }

        // Add disposition_time
        if (empty(DB::select("SHOW COLUMNS FROM `emergency_cases` LIKE 'disposition_time'"))) {
            DB::statement("ALTER TABLE `emergency_cases` ADD COLUMN `disposition_time` DATETIME NULL AFTER `disposition_notes`");
        }

        // Add disposed_by
        if (empty(DB::select("SHOW COLUMNS FROM `emergency_cases` LIKE 'disposed_by'"))) {
            DB::statement("ALTER TABLE `emergency_cases` ADD COLUMN `disposed_by` BIGINT UNSIGNED NULL AFTER `disposition_time`");
            DB::statement("ALTER TABLE `emergency_cases` ADD CONSTRAINT `ec_disposed_by_foreign` FOREIGN KEY (`disposed_by`) REFERENCES `users`(`id`) ON DELETE SET NULL");
        }
    }

    public function down(): void
    {
        if (! empty(DB::select("SHOW COLUMNS FROM `emergency_cases` LIKE 'emergency_status'"))) {
            DB::statement("ALTER TABLE `emergency_cases` CHANGE `emergency_status` `status` VARCHAR(60) NOT NULL DEFAULT 'ARRIVED'");
        }

        if (! empty(DB::select("SHOW COLUMNS FROM `emergency_cases` LIKE 'disposed_by'"))) {
            DB::statement("ALTER TABLE `emergency_cases` DROP FOREIGN KEY `ec_disposed_by_foreign`");
            DB::statement("ALTER TABLE `emergency_cases` DROP COLUMN `disposed_by`");
        }

        if (! empty(DB::select("SHOW COLUMNS FROM `emergency_cases` LIKE 'disposition_time'"))) {
            DB::statement("ALTER TABLE `emergency_cases` DROP COLUMN `disposition_time`");
        }

        if (! empty(DB::select("SHOW COLUMNS FROM `emergency_cases` LIKE 'disposition_notes'"))) {
            DB::statement("ALTER TABLE `emergency_cases` DROP COLUMN `disposition_notes`");
        }

        if (! empty(DB::select("SHOW COLUMNS FROM `emergency_cases` LIKE 'created_by'"))) {
            DB::statement("ALTER TABLE `emergency_cases` DROP FOREIGN KEY `ec_created_by_foreign`");
            DB::statement("ALTER TABLE `emergency_cases` DROP COLUMN `created_by`");
        }

        if (! empty(DB::select("SHOW COLUMNS FROM `emergency_cases` LIKE 'triage_notes'"))) {
            DB::statement("ALTER TABLE `emergency_cases` DROP COLUMN `triage_notes`");
        }

        if (! empty(DB::select("SHOW COLUMNS FROM `emergency_cases` LIKE 'triage_score'"))) {
            DB::statement("ALTER TABLE `emergency_cases` DROP COLUMN `triage_score`");
        }

        if (! empty(DB::select("SHOW COLUMNS FROM `emergency_cases` LIKE 'initial_condition'"))) {
            DB::statement("ALTER TABLE `emergency_cases` DROP COLUMN `initial_condition`");
        }

        if (! empty(DB::select("SHOW COLUMNS FROM `emergency_cases` LIKE 'source'"))) {
            DB::statement("ALTER TABLE `emergency_cases` DROP COLUMN `source`");
        }

        if (! empty(DB::select("SHOW COLUMNS FROM `emergency_cases` LIKE 'emergency_bay_id'"))) {
            DB::statement("ALTER TABLE `emergency_cases` DROP FOREIGN KEY `ec_emergency_bay_id_foreign`");
            DB::statement("ALTER TABLE `emergency_cases` DROP COLUMN `emergency_bay_id`");
        }

        if (! empty(DB::select("SHOW COLUMNS FROM `emergency_cases` LIKE 'admission_id'"))) {
            DB::statement("ALTER TABLE `emergency_cases` DROP FOREIGN KEY `ec_admission_id_foreign`");
            DB::statement("ALTER TABLE `emergency_cases` DROP COLUMN `admission_id`");
        }
    }
};
