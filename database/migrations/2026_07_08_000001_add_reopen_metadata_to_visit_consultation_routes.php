<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! $this->tableExists('visit_consultation_routes')) {
            return;
        }

        if (DB::getDriverName() === 'sqlite') {
            Schema::table('visit_consultation_routes', function (Blueprint $table) {
                if (! Schema::hasColumn('visit_consultation_routes', 'reopened_at')) {
                    $table->timestamp('reopened_at')->nullable();
                }
                if (! Schema::hasColumn('visit_consultation_routes', 'reopened_by')) {
                    $table->unsignedBigInteger('reopened_by')->nullable();
                }
                if (! Schema::hasColumn('visit_consultation_routes', 'reopen_reason')) {
                    $table->text('reopen_reason')->nullable();
                }
                if (! Schema::hasColumn('visit_consultation_routes', 'reopen_count')) {
                    $table->unsignedInteger('reopen_count')->default(0);
                }
            });

            return;
        }

        $adds = [];
        if (! $this->columnExists('visit_consultation_routes', 'reopened_at')) {
            $adds[] = 'ADD COLUMN `reopened_at` TIMESTAMP NULL DEFAULT NULL';
        }
        if (! $this->columnExists('visit_consultation_routes', 'reopened_by')) {
            $adds[] = 'ADD COLUMN `reopened_by` BIGINT UNSIGNED NULL';
        }
        if (! $this->columnExists('visit_consultation_routes', 'reopen_reason')) {
            $adds[] = 'ADD COLUMN `reopen_reason` TEXT NULL';
        }
        if (! $this->columnExists('visit_consultation_routes', 'reopen_count')) {
            $adds[] = 'ADD COLUMN `reopen_count` INT UNSIGNED NOT NULL DEFAULT 0';
        }

        if ($adds) {
            DB::statement('ALTER TABLE `visit_consultation_routes` '.implode(', ', $adds));
        }
    }

    public function down(): void
    {
        if (! $this->tableExists('visit_consultation_routes')) {
            return;
        }

        $columns = ['reopen_count', 'reopen_reason', 'reopened_by', 'reopened_at'];

        if (DB::getDriverName() === 'sqlite') {
            $existing = array_values(array_filter($columns, fn (string $column) => Schema::hasColumn('visit_consultation_routes', $column)));
            if ($existing) {
                Schema::table('visit_consultation_routes', fn (Blueprint $table) => $table->dropColumn($existing));
            }

            return;
        }

        $drops = [];
        foreach ($columns as $column) {
            if ($this->columnExists('visit_consultation_routes', $column)) {
                $drops[] = "DROP COLUMN `{$column}`";
            }
        }

        if ($drops) {
            DB::statement('ALTER TABLE `visit_consultation_routes` '.implode(', ', $drops));
        }
    }

    private function tableExists(string $table): bool
    {
        if (DB::getDriverName() === 'sqlite') {
            return Schema::hasTable($table);
        }

        $row = DB::selectOne(
            'SELECT COUNT(*) AS aggregate FROM information_schema.TABLES WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ?',
            [DB::getDatabaseName(), $table],
        );

        return (int) ($row->aggregate ?? 0) > 0;
    }

    private function columnExists(string $table, string $column): bool
    {
        if (DB::getDriverName() === 'sqlite') {
            return Schema::hasColumn($table, $column);
        }

        $row = DB::selectOne(
            'SELECT COUNT(*) AS aggregate FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND COLUMN_NAME = ?',
            [DB::getDatabaseName(), $table, $column],
        );

        return (int) ($row->aggregate ?? 0) > 0;
    }
};
