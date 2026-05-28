<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            Schema::table('patients', function (Blueprint $table) {
                if (! Schema::hasColumn('patients', 'is_active')) {
                    $table->boolean('is_active')->default(true);
                }

                if (! Schema::hasColumn('patients', 'merge_status')) {
                    $table->string('merge_status', 20)->default('ACTIVE');
                }

                if (! Schema::hasColumn('patients', 'merged_at')) {
                    $table->dateTime('merged_at')->nullable();
                }

                if (! Schema::hasColumn('patients', 'merged_by')) {
                    $table->unsignedBigInteger('merged_by')->nullable();
                }
            });

            return;
        }

        $this->ensureColumn('patients', 'is_active', "ALTER TABLE `patients` ADD COLUMN `is_active` TINYINT(1) NOT NULL DEFAULT 1 AFTER `status`");
        $this->ensureColumn('patients', 'merge_status', "ALTER TABLE `patients` ADD COLUMN `merge_status` VARCHAR(20) NOT NULL DEFAULT 'ACTIVE' AFTER `merged_to_patient_id`");
        $this->ensureColumn('patients', 'merged_at', "ALTER TABLE `patients` ADD COLUMN `merged_at` DATETIME NULL AFTER `merge_status`");
        $this->ensureColumn('patients', 'merged_by', "ALTER TABLE `patients` ADD COLUMN `merged_by` BIGINT UNSIGNED NULL AFTER `merged_at`");
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            Schema::table('patients', function (Blueprint $table) {
                foreach (['merged_by', 'merged_at', 'merge_status', 'is_active'] as $column) {
                    if (Schema::hasColumn('patients', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });

            return;
        }

        foreach (['merged_by', 'merged_at', 'merge_status', 'is_active'] as $column) {
            if ($this->hasColumn('patients', $column)) {
                DB::statement("ALTER TABLE `patients` DROP COLUMN `{$column}`");
            }
        }
    }

    private function ensureColumn(string $table, string $column, string $ddl): void
    {
        if (! $this->hasColumn($table, $column)) {
            DB::statement($ddl);
        }
    }

    private function hasColumn(string $table, string $column): bool
    {
        $quotedColumn = DB::getPdo()->quote($column);

        return ! empty(DB::select("SHOW COLUMNS FROM `{$table}` LIKE {$quotedColumn}"));
    }
};
