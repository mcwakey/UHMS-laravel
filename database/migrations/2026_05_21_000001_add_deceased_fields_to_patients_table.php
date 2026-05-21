<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('patients', 'is_deceased')) {
            return; // Already migrated
        }

        if (DB::getDriverName() === 'sqlite') {
            // SQLite: use Blueprint (no introspection bug)
            Schema::table('patients', function (Blueprint $table) {
                $table->boolean('is_deceased')->default(false)->after('status');
                $table->date('deceased_at')->nullable()->after('is_deceased');
                $table->string('cause_of_death', 255)->nullable()->after('deceased_at');
                $table->text('deceased_notes')->nullable()->after('cause_of_death');
                $table->foreignId('marked_deceased_by')->nullable()->constrained('users')->nullOnDelete()->after('deceased_notes');
            });
            return;
        }

        // MariaDB: raw DDL to avoid 'generation_expression' introspection bug
        DB::statement("ALTER TABLE `patients`
            ADD COLUMN `is_deceased` TINYINT(1) NOT NULL DEFAULT 0 AFTER `status`,
            ADD COLUMN `deceased_at` DATE NULL AFTER `is_deceased`,
            ADD COLUMN `cause_of_death` VARCHAR(255) NULL AFTER `deceased_at`,
            ADD COLUMN `deceased_notes` TEXT NULL AFTER `cause_of_death`,
            ADD COLUMN `marked_deceased_by` BIGINT UNSIGNED NULL AFTER `deceased_notes`
        ");

        DB::statement("ALTER TABLE `patients`
            ADD CONSTRAINT `patients_marked_deceased_by_foreign`
            FOREIGN KEY (`marked_deceased_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
        ");
    }

    public function down(): void
    {
        if (!Schema::hasColumn('patients', 'is_deceased')) {
            return;
        }

        if (DB::getDriverName() === 'sqlite') {
            Schema::table('patients', function (Blueprint $table) {
                $table->dropColumn(['is_deceased', 'deceased_at', 'cause_of_death', 'deceased_notes', 'marked_deceased_by']);
            });
            return;
        }

        // Check and drop FK first
        $fks = DB::select(
            "SELECT CONSTRAINT_NAME FROM information_schema.TABLE_CONSTRAINTS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'patients'
             AND CONSTRAINT_TYPE = 'FOREIGN KEY' AND CONSTRAINT_NAME = 'patients_marked_deceased_by_foreign'"
        );
        if (!empty($fks)) {
            DB::statement("ALTER TABLE `patients` DROP FOREIGN KEY `patients_marked_deceased_by_foreign`");
        }

        DB::statement("ALTER TABLE `patients`
            DROP COLUMN `is_deceased`,
            DROP COLUMN `deceased_at`,
            DROP COLUMN `cause_of_death`,
            DROP COLUMN `deceased_notes`,
            DROP COLUMN `marked_deceased_by`
        ");
    }
};
