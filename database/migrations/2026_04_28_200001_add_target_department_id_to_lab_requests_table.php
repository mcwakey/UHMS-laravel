<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private function cols(string $table): array
    {
        return array_map(fn ($r) => $r->Field, DB::select("SHOW COLUMNS FROM `{$table}`"));
    }

    public function up(): void
    {
        // Already added by 2026_04_28_100001 if it ran; skip if present.
        if (in_array('target_department_id', $this->cols('lab_requests'))) {
            return;
        }
        DB::statement('ALTER TABLE `lab_requests` ADD COLUMN `target_department_id` BIGINT UNSIGNED NULL AFTER `department_id`');
        DB::statement('ALTER TABLE `lab_requests` ADD CONSTRAINT `lab_requests_target_department_id_foreign` FOREIGN KEY (`target_department_id`) REFERENCES `departments` (`id`) ON DELETE SET NULL');
    }

    public function down(): void
    {
        if (!in_array('target_department_id', $this->cols('lab_requests'))) {
            return;
        }
        try { DB::statement('ALTER TABLE `lab_requests` DROP FOREIGN KEY `lab_requests_target_department_id_foreign`'); } catch (\Throwable $e) {}
        DB::statement('ALTER TABLE `lab_requests` DROP COLUMN `target_department_id`');
    }
};
