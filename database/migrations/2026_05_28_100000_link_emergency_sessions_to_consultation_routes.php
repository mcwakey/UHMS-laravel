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
            $this->upSqlite();
            return;
        }

        $this->upMysql();
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            $this->downSqlite();
            return;
        }

        $this->downMysql();
    }

    private function upSqlite(): void
    {
        if (Schema::hasTable('visit_consultation_routes')) {
            Schema::table('visit_consultation_routes', function (Blueprint $table) {
                if (! Schema::hasColumn('visit_consultation_routes', 'session_type')) {
                    $table->string('session_type', 40)->default('CONSULTATION')->index('vcr_session_type_idx');
                }
                if (! Schema::hasColumn('visit_consultation_routes', 'emergency_case_id')) {
                    $table->unsignedBigInteger('emergency_case_id')->nullable()->index('vcr_emergency_case_idx');
                }
                if (! Schema::hasColumn('visit_consultation_routes', 'main_doctor_id')) {
                    $table->unsignedBigInteger('main_doctor_id')->nullable()->index('vcr_main_doctor_idx');
                }
                if (! Schema::hasColumn('visit_consultation_routes', 'primary_nurse_id')) {
                    $table->unsignedBigInteger('primary_nurse_id')->nullable()->index('vcr_primary_nurse_idx');
                }
            });
        }

        if (Schema::hasTable('emergency_sessions')) {
            Schema::table('emergency_sessions', function (Blueprint $table) {
                if (! Schema::hasColumn('emergency_sessions', 'consultation_route_id')) {
                    $table->unsignedBigInteger('consultation_route_id')->nullable()->index('er_sessions_route_idx');
                }
            });
        }

        foreach ($this->clinicalLinkTables() as $tableName => $indexes) {
            if (! Schema::hasTable($tableName)) {
                continue;
            }

            Schema::table($tableName, function (Blueprint $table) use ($tableName, $indexes) {
                if (! Schema::hasColumn($tableName, 'medical_record_id')) {
                    $table->unsignedBigInteger('medical_record_id')->nullable()->index($indexes['medical_record_id']);
                }
                if (! Schema::hasColumn($tableName, 'consultation_route_id')) {
                    $table->unsignedBigInteger('consultation_route_id')->nullable()->index($indexes['consultation_route_id']);
                }
            });
        }
    }

    private function upMysql(): void
    {
        $this->addColumn('visit_consultation_routes', 'session_type', "ALTER TABLE `visit_consultation_routes` ADD COLUMN `session_type` VARCHAR(40) NOT NULL DEFAULT 'CONSULTATION' AFTER `status`", 'CREATE INDEX `vcr_session_type_idx` ON `visit_consultation_routes` (`session_type`)');
        $this->addColumn('visit_consultation_routes', 'emergency_case_id', 'ALTER TABLE `visit_consultation_routes` ADD COLUMN `emergency_case_id` BIGINT UNSIGNED NULL AFTER `patient_id`', 'CREATE INDEX `vcr_emergency_case_idx` ON `visit_consultation_routes` (`emergency_case_id`)');
        $this->addColumn('visit_consultation_routes', 'main_doctor_id', 'ALTER TABLE `visit_consultation_routes` ADD COLUMN `main_doctor_id` BIGINT UNSIGNED NULL AFTER `doctor_id`', 'CREATE INDEX `vcr_main_doctor_idx` ON `visit_consultation_routes` (`main_doctor_id`)');
        $this->addColumn('visit_consultation_routes', 'primary_nurse_id', 'ALTER TABLE `visit_consultation_routes` ADD COLUMN `primary_nurse_id` BIGINT UNSIGNED NULL AFTER `main_doctor_id`', 'CREATE INDEX `vcr_primary_nurse_idx` ON `visit_consultation_routes` (`primary_nurse_id`)');

        $this->addColumn('emergency_sessions', 'consultation_route_id', 'ALTER TABLE `emergency_sessions` ADD COLUMN `consultation_route_id` BIGINT UNSIGNED NULL AFTER `emergency_case_id`', 'CREATE INDEX `er_sessions_route_idx` ON `emergency_sessions` (`consultation_route_id`)');

        foreach ($this->clinicalLinkTables() as $tableName => $indexes) {
            $this->addColumn($tableName, 'medical_record_id', "ALTER TABLE `{$tableName}` ADD COLUMN `medical_record_id` BIGINT UNSIGNED NULL", "CREATE INDEX `{$indexes['medical_record_id']}` ON `{$tableName}` (`medical_record_id`)");
            $this->addColumn($tableName, 'consultation_route_id', "ALTER TABLE `{$tableName}` ADD COLUMN `consultation_route_id` BIGINT UNSIGNED NULL", "CREATE INDEX `{$indexes['consultation_route_id']}` ON `{$tableName}` (`consultation_route_id`)");
        }
    }

    private function downSqlite(): void
    {
        foreach ($this->clinicalLinkTables() as $tableName => $indexes) {
            $this->dropSqliteColumns($tableName, ['medical_record_id', 'consultation_route_id']);
        }

        $this->dropSqliteColumns('emergency_sessions', ['consultation_route_id']);
        $this->dropSqliteColumns('visit_consultation_routes', ['session_type', 'emergency_case_id', 'main_doctor_id', 'primary_nurse_id']);
    }

    private function downMysql(): void
    {
        foreach ($this->clinicalLinkTables() as $tableName => $indexes) {
            $this->dropColumn($tableName, 'consultation_route_id', $indexes['consultation_route_id']);
            $this->dropColumn($tableName, 'medical_record_id', $indexes['medical_record_id']);
        }

        $this->dropColumn('emergency_sessions', 'consultation_route_id', 'er_sessions_route_idx');
        $this->dropColumn('visit_consultation_routes', 'primary_nurse_id', 'vcr_primary_nurse_idx');
        $this->dropColumn('visit_consultation_routes', 'main_doctor_id', 'vcr_main_doctor_idx');
        $this->dropColumn('visit_consultation_routes', 'emergency_case_id', 'vcr_emergency_case_idx');
        $this->dropColumn('visit_consultation_routes', 'session_type', 'vcr_session_type_idx');
    }

    private function clinicalLinkTables(): array
    {
        return [
            'emergency_notes' => ['medical_record_id' => 'er_notes_mr_idx', 'consultation_route_id' => 'er_notes_route_idx'],
            'vitals' => ['medical_record_id' => 'vitals_mr_idx', 'consultation_route_id' => 'vitals_route_idx'],
            'lab_requests' => ['medical_record_id' => 'labs_mr_idx', 'consultation_route_id' => 'labs_route_idx'],
            'procedure_requests' => ['medical_record_id' => 'proc_mr_idx', 'consultation_route_id' => 'proc_route_idx'],
            'clinical_tasks' => ['medical_record_id' => 'tasks_mr_idx', 'consultation_route_id' => 'tasks_route_idx'],
            'consumable_usages' => ['medical_record_id' => 'cu_mr_idx', 'consultation_route_id' => 'cu_route_idx'],
            'medication_administration_schedules' => ['medical_record_id' => 'mas_mr_idx', 'consultation_route_id' => 'mas_route_idx'],
            'medication_administrations' => ['medical_record_id' => 'ma_mr_idx', 'consultation_route_id' => 'ma_route_idx'],
        ];
    }

    private function addColumn(string $table, string $column, string $sql, ?string $indexSql = null): void
    {
        if (! $this->tableExists($table) || $this->columnExists($table, $column)) {
            return;
        }

        DB::statement($sql);

        if ($indexSql) {
            try {
                DB::statement($indexSql);
            } catch (\Throwable) {
            }
        }
    }

    private function dropSqliteColumns(string $table, array $columns): void
    {
        if (! Schema::hasTable($table)) {
            return;
        }

        $existing = array_values(array_filter($columns, fn (string $column) => Schema::hasColumn($table, $column)));
        if ($existing === []) {
            return;
        }

        Schema::table($table, function (Blueprint $table) use ($existing) {
            $table->dropColumn($existing);
        });
    }

    private function dropColumn(string $table, string $column, ?string $indexName = null): void
    {
        if (! $this->tableExists($table) || ! $this->columnExists($table, $column)) {
            return;
        }

        if ($indexName) {
            try {
                DB::statement("DROP INDEX `{$indexName}` ON `{$table}`");
            } catch (\Throwable) {
            }
        }

        DB::statement("ALTER TABLE `{$table}` DROP COLUMN `{$column}`");
    }

    private function tableExists(string $table): bool
    {
        $row = DB::selectOne(
            'SELECT COUNT(*) AS aggregate FROM information_schema.TABLES WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ?',
            [DB::getDatabaseName(), $table]
        );

        return (int) ($row->aggregate ?? 0) > 0;
    }

    private function columnExists(string $table, string $column): bool
    {
        $row = DB::selectOne(
            'SELECT COUNT(*) AS aggregate FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND COLUMN_NAME = ?',
            [DB::getDatabaseName(), $table, $column]
        );

        return (int) ($row->aggregate ?? 0) > 0;
    }
};