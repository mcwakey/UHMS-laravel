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
            if (! Schema::hasColumn('visits', 'verification_reference_code')) {
                Schema::table('visits', function (Blueprint $table) {
                    $table->string('verification_reference_code', 100)->nullable();
                });
            }
        } else {
            $exists = DB::selectOne(
                "SELECT COUNT(*) AS cnt FROM information_schema.COLUMNS
                 WHERE TABLE_SCHEMA = DATABASE()
                   AND TABLE_NAME = 'visits'
                   AND COLUMN_NAME = 'verification_reference_code'"
            );
            if (! $exists || $exists->cnt == 0) {
                DB::statement(
                    "ALTER TABLE `visits`
                     ADD COLUMN `verification_reference_code` VARCHAR(100) NULL DEFAULT NULL
                     AFTER `insurance_verification_id`"
                );
            }
        }
    }

    public function down(): void
    {
        $driver = DB::getDriverName();

        if ($driver === 'sqlite') {
            if (Schema::hasColumn('visits', 'verification_reference_code')) {
                Schema::table('visits', function (Blueprint $table) {
                    $table->dropColumn('verification_reference_code');
                });
            }
        } else {
            $exists = DB::selectOne(
                "SELECT COUNT(*) AS cnt FROM information_schema.COLUMNS
                 WHERE TABLE_SCHEMA = DATABASE()
                   AND TABLE_NAME = 'visits'
                   AND COLUMN_NAME = 'verification_reference_code'"
            );
            if ($exists && $exists->cnt > 0) {
                DB::statement("ALTER TABLE `visits` DROP COLUMN `verification_reference_code`");
            }
        }
    }
};
