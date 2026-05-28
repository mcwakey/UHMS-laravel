<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->createEmergencySessions();
        $this->createEmergencySessionContributors();
        $this->createEmergencyBayAssignments();

        if (DB::getDriverName() === 'sqlite') {
            $this->addSqliteColumns();

            return;
        }

        $this->addMysqlColumns();
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            $this->dropSqliteColumns();
        } else {
            $this->dropMysqlColumns();
        }

        foreach (['emergency_bay_assignments', 'emergency_session_contributors', 'emergency_sessions'] as $table) {
            if (! $this->tableExists($table)) {
                continue;
            }

            if (DB::getDriverName() === 'sqlite') {
                Schema::drop($table);
            } else {
                DB::statement("DROP TABLE `{$table}`");
            }
        }
    }

    private function createEmergencySessions(): void
    {
        if ($this->tableExists('emergency_sessions')) {
            return;
        }

        Schema::create('emergency_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('emergency_case_id')->constrained('emergency_cases')->cascadeOnDelete();
            $table->foreignId('visit_id')->constrained('visits')->cascadeOnDelete();
            $table->foreignId('patient_id')->constrained('patients')->cascadeOnDelete();
            $table->foreignId('department_id')->nullable()->constrained('departments')->nullOnDelete();
            $table->foreignId('medical_record_id')->nullable()->constrained('medical_records')->nullOnDelete();
            $table->foreignId('main_doctor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('primary_nurse_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status', 40)->default('ACTIVE');
            $table->dateTime('started_at')->nullable();
            $table->dateTime('ended_at')->nullable();
            $table->foreignId('started_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('ended_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['emergency_case_id', 'status'], 'er_sessions_case_status_idx');
            $table->index(['visit_id', 'status'], 'er_sessions_visit_status_idx');
        });
    }

    private function createEmergencySessionContributors(): void
    {
        if ($this->tableExists('emergency_session_contributors')) {
            return;
        }

        Schema::create('emergency_session_contributors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('emergency_session_id')->constrained('emergency_sessions')->cascadeOnDelete();
            $table->foreignId('emergency_case_id')->constrained('emergency_cases')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('role')->nullable();
            $table->timestamp('first_contributed_at')->nullable();
            $table->timestamp('last_contributed_at')->nullable();
            $table->timestamps();

            $table->unique(['emergency_session_id', 'user_id'], 'er_session_user_unique');
            $table->index(['emergency_case_id', 'user_id'], 'er_contrib_case_user_idx');
        });
    }

    private function createEmergencyBayAssignments(): void
    {
        if ($this->tableExists('emergency_bay_assignments')) {
            return;
        }

        Schema::create('emergency_bay_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('emergency_case_id')->constrained('emergency_cases')->cascadeOnDelete();
            $table->foreignId('emergency_session_id')->nullable()->constrained('emergency_sessions')->nullOnDelete();
            $table->foreignId('ward_id')->nullable()->constrained('wards')->nullOnDelete();
            $table->foreignId('bed_id')->nullable()->constrained('beds')->nullOnDelete();
            $table->foreignId('emergency_bay_id')->nullable()->constrained('emergency_bays')->nullOnDelete();
            $table->foreignId('assigned_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('assigned_at')->nullable();
            $table->foreignId('released_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('released_at')->nullable();
            $table->string('status', 40)->default('ACTIVE');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['emergency_case_id', 'status'], 'er_bay_assign_case_status_idx');
            $table->index(['bed_id', 'status'], 'er_bay_assign_bed_status_idx');
            $table->index(['emergency_bay_id', 'status'], 'er_bay_assign_bay_status_idx');
        });
    }

    private function addSqliteColumns(): void
    {
        if (Schema::hasTable('emergency_cases')) {
            Schema::table('emergency_cases', function (Blueprint $table) {
                if (! Schema::hasColumn('emergency_cases', 'auto_triage_category')) $table->string('auto_triage_category', 20)->nullable()->after('triage_category');
                if (! Schema::hasColumn('emergency_cases', 'final_triage_category')) $table->string('final_triage_category', 20)->nullable()->after('auto_triage_category');
                if (! Schema::hasColumn('emergency_cases', 'triage_override_reason')) $table->text('triage_override_reason')->nullable()->after('triage_notes');
                if (! Schema::hasColumn('emergency_cases', 'triage_reasons')) $table->longText('triage_reasons')->nullable()->after('triage_override_reason');
                if (! Schema::hasColumn('emergency_cases', 'triage_warnings')) $table->longText('triage_warnings')->nullable()->after('triage_reasons');
                if (! Schema::hasColumn('emergency_cases', 'avpu')) $table->string('avpu', 20)->nullable()->after('triage_warnings');
                if (! Schema::hasColumn('emergency_cases', 'pain_score')) $table->unsignedTinyInteger('pain_score')->nullable()->after('avpu');
                if (! Schema::hasColumn('emergency_cases', 'danger_signs')) $table->longText('danger_signs')->nullable()->after('pain_score');
            });
        }

        if (Schema::hasTable('emergency_bays')) {
            Schema::table('emergency_bays', function (Blueprint $table) {
                if (! Schema::hasColumn('emergency_bays', 'ward_id')) $table->unsignedBigInteger('ward_id')->nullable()->after('department_id')->index('er_bays_ward_idx');
                if (! Schema::hasColumn('emergency_bays', 'bed_id')) $table->unsignedBigInteger('bed_id')->nullable()->after('ward_id')->index('er_bays_bed_idx');
            });
        }

        foreach ($this->sessionLinkedTables() as $tableName => $indexName) {
            if (! Schema::hasTable($tableName)) {
                continue;
            }

            Schema::table($tableName, function (Blueprint $table) use ($tableName, $indexName) {
                if (! Schema::hasColumn($tableName, 'emergency_session_id')) {
                    $table->unsignedBigInteger('emergency_session_id')->nullable()->index($indexName);
                }
            });
        }

        if (Schema::hasTable('consumable_usages')) {
            Schema::table('consumable_usages', function (Blueprint $table) {
                if (! Schema::hasColumn('consumable_usages', 'emergency_case_id')) $table->unsignedBigInteger('emergency_case_id')->nullable()->index('cu_er_case_idx');
            });
        }
    }

    private function addMysqlColumns(): void
    {
        $this->addColumn('emergency_cases', 'auto_triage_category', 'ALTER TABLE `emergency_cases` ADD COLUMN `auto_triage_category` VARCHAR(20) NULL AFTER `triage_category`');
        $this->addColumn('emergency_cases', 'final_triage_category', 'ALTER TABLE `emergency_cases` ADD COLUMN `final_triage_category` VARCHAR(20) NULL AFTER `auto_triage_category`');
        $this->addColumn('emergency_cases', 'triage_override_reason', 'ALTER TABLE `emergency_cases` ADD COLUMN `triage_override_reason` TEXT NULL AFTER `triage_notes`');
        $this->addColumn('emergency_cases', 'triage_reasons', 'ALTER TABLE `emergency_cases` ADD COLUMN `triage_reasons` LONGTEXT NULL AFTER `triage_override_reason`');
        $this->addColumn('emergency_cases', 'triage_warnings', 'ALTER TABLE `emergency_cases` ADD COLUMN `triage_warnings` LONGTEXT NULL AFTER `triage_reasons`');
        $this->addColumn('emergency_cases', 'avpu', 'ALTER TABLE `emergency_cases` ADD COLUMN `avpu` VARCHAR(20) NULL AFTER `triage_warnings`');
        $this->addColumn('emergency_cases', 'pain_score', 'ALTER TABLE `emergency_cases` ADD COLUMN `pain_score` TINYINT UNSIGNED NULL AFTER `avpu`');
        $this->addColumn('emergency_cases', 'danger_signs', 'ALTER TABLE `emergency_cases` ADD COLUMN `danger_signs` LONGTEXT NULL AFTER `pain_score`');

        $this->addColumn('emergency_bays', 'ward_id', 'ALTER TABLE `emergency_bays` ADD COLUMN `ward_id` BIGINT UNSIGNED NULL AFTER `department_id`', 'CREATE INDEX `er_bays_ward_idx` ON `emergency_bays` (`ward_id`)');
        $this->addColumn('emergency_bays', 'bed_id', 'ALTER TABLE `emergency_bays` ADD COLUMN `bed_id` BIGINT UNSIGNED NULL AFTER `ward_id`', 'CREATE INDEX `er_bays_bed_idx` ON `emergency_bays` (`bed_id`)');

        foreach ($this->sessionLinkedTables() as $tableName => $indexName) {
            $this->addColumn($tableName, 'emergency_session_id', "ALTER TABLE `{$tableName}` ADD COLUMN `emergency_session_id` BIGINT UNSIGNED NULL", "CREATE INDEX `{$indexName}` ON `{$tableName}` (`emergency_session_id`)");
        }

        $this->addColumn('consumable_usages', 'emergency_case_id', 'ALTER TABLE `consumable_usages` ADD COLUMN `emergency_case_id` BIGINT UNSIGNED NULL AFTER `visit_id`', 'CREATE INDEX `cu_er_case_idx` ON `consumable_usages` (`emergency_case_id`)');
    }

    private function dropSqliteColumns(): void
    {
        if (Schema::hasTable('consumable_usages')) {
            Schema::table('consumable_usages', function (Blueprint $table) {
                if (Schema::hasColumn('consumable_usages', 'emergency_case_id')) $table->dropColumn('emergency_case_id');
            });
        }

        foreach ($this->sessionLinkedTables() as $tableName => $indexName) {
            if (! Schema::hasTable($tableName) || ! Schema::hasColumn($tableName, 'emergency_session_id')) {
                continue;
            }
            Schema::table($tableName, fn (Blueprint $table) => $table->dropColumn('emergency_session_id'));
        }

        if (Schema::hasTable('emergency_bays')) {
            Schema::table('emergency_bays', function (Blueprint $table) {
                foreach (['bed_id', 'ward_id'] as $column) {
                    if (Schema::hasColumn('emergency_bays', $column)) $table->dropColumn($column);
                }
            });
        }

        if (Schema::hasTable('emergency_cases')) {
            Schema::table('emergency_cases', function (Blueprint $table) {
                foreach (['auto_triage_category', 'final_triage_category', 'triage_override_reason', 'triage_reasons', 'triage_warnings', 'avpu', 'pain_score', 'danger_signs'] as $column) {
                    if (Schema::hasColumn('emergency_cases', $column)) $table->dropColumn($column);
                }
            });
        }
    }

    private function dropMysqlColumns(): void
    {
        $this->dropColumn('consumable_usages', 'emergency_case_id', 'cu_er_case_idx');

        foreach ($this->sessionLinkedTables() as $tableName => $indexName) {
            $this->dropColumn($tableName, 'emergency_session_id', $indexName);
        }

        $this->dropColumn('emergency_bays', 'bed_id', 'er_bays_bed_idx');
        $this->dropColumn('emergency_bays', 'ward_id', 'er_bays_ward_idx');

        foreach (['danger_signs', 'pain_score', 'avpu', 'triage_warnings', 'triage_reasons', 'triage_override_reason', 'final_triage_category', 'auto_triage_category'] as $column) {
            $this->dropColumn('emergency_cases', $column);
        }
    }

    private function sessionLinkedTables(): array
    {
        return [
            'emergency_notes' => 'er_notes_session_idx',
            'vitals' => 'vitals_er_session_idx',
            'lab_requests' => 'labs_er_session_idx',
            'procedure_requests' => 'proc_er_session_idx',
            'medication_orders' => 'med_orders_er_sess_idx',
            'medication_administration_schedules' => 'mas_er_sess_idx',
            'medication_administrations' => 'ma_er_sess_idx',
            'clinical_tasks' => 'tasks_er_sess_idx',
            'consumable_usages' => 'cu_er_session_idx',
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
        if (DB::getDriverName() === 'sqlite') {
            return Schema::hasTable($table);
        }

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