<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'sqlite') return;

        $col = collect(DB::select('SHOW COLUMNS FROM `lab_request_items`'))->firstWhere('Field', 'lab_test_id');
        if ($col && $col->Null === 'YES') return; // already nullable

        try { DB::statement('ALTER TABLE `lab_request_items` DROP FOREIGN KEY `lab_request_items_lab_test_id_foreign`'); } catch (\Throwable $e) {}
        DB::statement('ALTER TABLE `lab_request_items` MODIFY COLUMN `lab_test_id` BIGINT UNSIGNED NULL');
        try { DB::statement('ALTER TABLE `lab_request_items` ADD CONSTRAINT `lab_request_items_lab_test_id_foreign` FOREIGN KEY (`lab_test_id`) REFERENCES `lab_tests` (`id`) ON DELETE SET NULL'); } catch (\Throwable $e) {}
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'sqlite') return;

        $col = collect(DB::select('SHOW COLUMNS FROM `lab_request_items`'))->firstWhere('Field', 'lab_test_id');
        if (!$col || $col->Null === 'NO') return;

        try { DB::statement('ALTER TABLE `lab_request_items` DROP FOREIGN KEY `lab_request_items_lab_test_id_foreign`'); } catch (\Throwable $e) {}
        DB::statement('ALTER TABLE `lab_request_items` MODIFY COLUMN `lab_test_id` BIGINT UNSIGNED NOT NULL');
        try { DB::statement('ALTER TABLE `lab_request_items` ADD CONSTRAINT `lab_request_items_lab_test_id_foreign` FOREIGN KEY (`lab_test_id`) REFERENCES `lab_tests` (`id`) ON DELETE CASCADE'); } catch (\Throwable $e) {}
    }
};
