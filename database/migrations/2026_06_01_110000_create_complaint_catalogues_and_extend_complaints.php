<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! $this->tableExists('complaint_catalogues')) {
            Schema::create('complaint_catalogues', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('category')->nullable();
                $table->string('body_system')->nullable();
                $table->text('description')->nullable();
                $table->text('keywords')->nullable();
                $table->boolean('is_active')->default(true);
                $table->integer('sort_order')->nullable();
                $table->timestamps();

                $table->unique(['name', 'category'], 'complaint_catalogues_name_category_unique');
                $table->index(['is_active', 'category', 'sort_order'], 'complaint_catalogues_active_category_idx');
            });
        }

        if (! $this->tableExists('complaints')) {
            return;
        }

        $this->addComplaintColumn('complaint_catalogue_id', 'BIGINT UNSIGNED NULL', function (Blueprint $table) {
            $table->foreignId('complaint_catalogue_id')->nullable()->constrained('complaint_catalogues')->nullOnDelete();
        }, 'complaints_catalogue_id_foreign', 'ALTER TABLE `complaints` ADD CONSTRAINT `complaints_catalogue_id_foreign` FOREIGN KEY (`complaint_catalogue_id`) REFERENCES `complaint_catalogues` (`id`) ON DELETE SET NULL');

        $this->addComplaintColumn('emergency_case_id', 'BIGINT UNSIGNED NULL', function (Blueprint $table) {
            $table->foreignId('emergency_case_id')->nullable()->constrained('emergency_cases')->nullOnDelete();
        }, 'complaints_emergency_case_id_foreign', 'ALTER TABLE `complaints` ADD CONSTRAINT `complaints_emergency_case_id_foreign` FOREIGN KEY (`emergency_case_id`) REFERENCES `emergency_cases` (`id`) ON DELETE SET NULL');

        $this->addComplaintColumn('emergency_session_id', 'BIGINT UNSIGNED NULL', function (Blueprint $table) {
            $table->foreignId('emergency_session_id')->nullable()->constrained('emergency_sessions')->nullOnDelete();
        }, 'complaints_emergency_session_id_foreign', 'ALTER TABLE `complaints` ADD CONSTRAINT `complaints_emergency_session_id_foreign` FOREIGN KEY (`emergency_session_id`) REFERENCES `emergency_sessions` (`id`) ON DELETE SET NULL');

        $this->addComplaintColumn('admission_id', 'BIGINT UNSIGNED NULL', function (Blueprint $table) {
            $table->foreignId('admission_id')->nullable()->constrained('admissions')->nullOnDelete();
        }, 'complaints_admission_id_foreign', 'ALTER TABLE `complaints` ADD CONSTRAINT `complaints_admission_id_foreign` FOREIGN KEY (`admission_id`) REFERENCES `admissions` (`id`) ON DELETE SET NULL');

        $this->addComplaintColumn('duration_unit', 'VARCHAR(20) NULL', function (Blueprint $table) {
            $table->string('duration_unit', 20)->nullable();
        });

        $this->addComplaintColumn('notes', 'TEXT NULL', function (Blueprint $table) {
            $table->text('notes')->nullable();
        });

        $this->addMysqlIndexIfMissing('complaints', 'complaints_catalogue_id_idx', ['complaint_catalogue_id']);
        $this->addMysqlIndexIfMissing('complaints', 'complaints_emergency_case_id_idx', ['emergency_case_id']);
        $this->addMysqlIndexIfMissing('complaints', 'complaints_emergency_session_id_idx', ['emergency_session_id']);
        $this->addMysqlIndexIfMissing('complaints', 'complaints_admission_id_idx', ['admission_id']);
    }

    public function down(): void
    {
        if ($this->tableExists('complaints')) {
            foreach ([
                'complaints_admission_id_foreign',
                'complaints_emergency_session_id_foreign',
                'complaints_emergency_case_id_foreign',
                'complaints_catalogue_id_foreign',
            ] as $constraint) {
                $this->dropMysqlForeignIfExists('complaints', $constraint);
            }

            foreach (['notes', 'duration_unit', 'admission_id', 'emergency_session_id', 'emergency_case_id', 'complaint_catalogue_id'] as $column) {
                $this->dropColumnIfExists('complaints', $column);
            }
        }

        Schema::dropIfExists('complaint_catalogues');
    }

    private function addComplaintColumn(string $column, string $mysqlDefinition, callable $sqliteDefinition, ?string $constraintName = null, ?string $constraintSql = null): void
    {
        $driver = DB::getDriverName();
        if ($this->hasColumn('complaints', $column)) {
            return;
        }

        if ($driver === 'sqlite') {
            Schema::table('complaints', $sqliteDefinition);
            return;
        }

        DB::statement("ALTER TABLE `complaints` ADD COLUMN `{$column}` {$mysqlDefinition}");

        if ($constraintName && $constraintSql && ! $this->mysqlHasForeignKey('complaints', $constraintName)) {
            DB::statement($constraintSql);
        }
    }

    private function dropColumnIfExists(string $table, string $column): void
    {
        if (! $this->hasColumn($table, $column)) {
            return;
        }

        if (DB::getDriverName() === 'sqlite') {
            Schema::table($table, fn (Blueprint $blueprint) => $blueprint->dropColumn($column));
            return;
        }

        DB::statement("ALTER TABLE `{$table}` DROP COLUMN `{$column}`");
    }

    private function tableExists(string $table): bool
    {
        if (DB::getDriverName() === 'sqlite') {
            $result = DB::selectOne("SELECT COUNT(*) AS cnt FROM sqlite_master WHERE type = 'table' AND name = ?", [$table]);
            return (int) ($result->cnt ?? 0) > 0;
        }

        $result = DB::selectOne(
            'SELECT COUNT(*) AS cnt FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?',
            [$table]
        );

        return (int) ($result->cnt ?? 0) > 0;
    }

    private function hasColumn(string $table, string $column): bool
    {
        if (DB::getDriverName() === 'sqlite') {
            return Schema::hasColumn($table, $column);
        }

        $result = DB::selectOne(
            'SELECT COUNT(*) AS cnt FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?',
            [$table, $column]
        );

        return (int) ($result->cnt ?? 0) > 0;
    }

    private function addMysqlIndexIfMissing(string $table, string $index, array $columns): void
    {
        if (DB::getDriverName() === 'sqlite' || $this->mysqlHasIndex($table, $index)) {
            return;
        }

        $columnSql = implode('`, `', $columns);
        DB::statement("ALTER TABLE `{$table}` ADD INDEX `{$index}` (`{$columnSql}`)");
    }

    private function mysqlHasIndex(string $table, string $index): bool
    {
        $result = DB::selectOne(
            'SELECT COUNT(*) AS cnt FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND INDEX_NAME = ?',
            [$table, $index]
        );

        return (int) ($result->cnt ?? 0) > 0;
    }

    private function mysqlHasForeignKey(string $table, string $constraint): bool
    {
        $result = DB::selectOne(
            'SELECT COUNT(*) AS cnt FROM information_schema.TABLE_CONSTRAINTS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND CONSTRAINT_NAME = ?',
            [$table, $constraint]
        );

        return (int) ($result->cnt ?? 0) > 0;
    }

    private function dropMysqlForeignIfExists(string $table, string $constraint): void
    {
        if (DB::getDriverName() !== 'sqlite' && $this->mysqlHasForeignKey($table, $constraint)) {
            DB::statement("ALTER TABLE `{$table}` DROP FOREIGN KEY `{$constraint}`");
        }
    }
};