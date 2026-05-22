<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $driver = DB::getDriverName();

        if ($driver === 'sqlite') {
            if (! Schema::hasColumn('visit_services', 'assigned_staff_id')) {
                Schema::table('visit_services', function (Blueprint $table) {
                    $table->unsignedBigInteger('assigned_staff_id')->nullable();
                });
            }
        } else {
            $exists = DB::selectOne(
                "SELECT COUNT(*) AS cnt FROM information_schema.COLUMNS
                 WHERE TABLE_SCHEMA = DATABASE()
                   AND TABLE_NAME = 'visit_services'
                   AND COLUMN_NAME = 'assigned_staff_id'"
            );
            if (! $exists || $exists->cnt == 0) {
                DB::statement(
                    "ALTER TABLE `visit_services`
                     ADD COLUMN `assigned_staff_id` BIGINT UNSIGNED NULL DEFAULT NULL
                     AFTER `notes`"
                );
            }

            $fkExists = DB::selectOne(
                "SELECT COUNT(*) AS cnt FROM information_schema.TABLE_CONSTRAINTS
                 WHERE TABLE_SCHEMA = DATABASE()
                   AND TABLE_NAME = 'visit_services'
                   AND CONSTRAINT_NAME = 'visit_services_assigned_staff_id_foreign'"
            );
            if (! $fkExists || $fkExists->cnt == 0) {
                DB::statement(
                    "ALTER TABLE `visit_services`
                     ADD CONSTRAINT `visit_services_assigned_staff_id_foreign`
                     FOREIGN KEY (`assigned_staff_id`) REFERENCES `users` (`id`) ON DELETE SET NULL"
                );
            }
        }
    }

    public function down(): void
    {
        $driver = DB::getDriverName();

        if ($driver !== 'sqlite') {
            $fkExists = DB::selectOne(
                "SELECT COUNT(*) AS cnt FROM information_schema.TABLE_CONSTRAINTS
                 WHERE TABLE_SCHEMA = DATABASE()
                   AND TABLE_NAME = 'visit_services'
                   AND CONSTRAINT_NAME = 'visit_services_assigned_staff_id_foreign'"
            );
            if ($fkExists && $fkExists->cnt > 0) {
                DB::statement("ALTER TABLE `visit_services` DROP FOREIGN KEY `visit_services_assigned_staff_id_foreign`");
            }

            $exists = DB::selectOne(
                "SELECT COUNT(*) AS cnt FROM information_schema.COLUMNS
                 WHERE TABLE_SCHEMA = DATABASE()
                   AND TABLE_NAME = 'visit_services'
                   AND COLUMN_NAME = 'assigned_staff_id'"
            );
            if ($exists && $exists->cnt > 0) {
                DB::statement("ALTER TABLE `visit_services` DROP COLUMN `assigned_staff_id`");
            }
        } else {
            if (Schema::hasColumn('visit_services', 'assigned_staff_id')) {
                Schema::table('visit_services', function (Blueprint $table) {
                    $table->dropColumn('assigned_staff_id');
                });
            }
        }
    }
};
