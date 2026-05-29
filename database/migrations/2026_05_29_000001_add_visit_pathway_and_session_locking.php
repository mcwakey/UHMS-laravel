<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! $this->tableExists('visit_pathway_events')) {
            Schema::create('visit_pathway_events', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('visit_id')->index();
                $table->unsignedBigInteger('patient_id')->index();
                $table->string('event_type', 80)->index();
                $table->unsignedBigInteger('department_id')->nullable()->index();
                $table->string('source_type', 160)->nullable();
                $table->unsignedBigInteger('source_id')->nullable();
                $table->string('status', 80)->nullable();
                $table->string('title');
                $table->text('description')->nullable();
                $table->dateTime('started_at')->nullable()->index();
                $table->dateTime('completed_at')->nullable();
                $table->unsignedBigInteger('created_by')->nullable()->index();
                $table->timestamps();

                $table->index(['visit_id', 'started_at']);
                $table->index(['source_type', 'source_id']);
            });
        }

        $this->addColumns('visits', [
            'completed_at' => "ALTER TABLE `visits` ADD COLUMN `completed_at` TIMESTAMP NULL DEFAULT NULL",
            'completed_by' => "ALTER TABLE `visits` ADD COLUMN `completed_by` BIGINT UNSIGNED NULL",
            'locked_at' => "ALTER TABLE `visits` ADD COLUMN `locked_at` TIMESTAMP NULL DEFAULT NULL",
            'locked_by' => "ALTER TABLE `visits` ADD COLUMN `locked_by` BIGINT UNSIGNED NULL",
            'lock_reason' => "ALTER TABLE `visits` ADD COLUMN `lock_reason` TEXT NULL",
        ], function (Blueprint $table) {
            if (! Schema::hasColumn('visits', 'completed_at')) $table->timestamp('completed_at')->nullable();
            if (! Schema::hasColumn('visits', 'completed_by')) $table->unsignedBigInteger('completed_by')->nullable();
            if (! Schema::hasColumn('visits', 'locked_at')) $table->timestamp('locked_at')->nullable();
            if (! Schema::hasColumn('visits', 'locked_by')) $table->unsignedBigInteger('locked_by')->nullable();
            if (! Schema::hasColumn('visits', 'lock_reason')) $table->text('lock_reason')->nullable();
        });

        $this->addColumns('visit_consultation_routes', [
            'locked_at' => "ALTER TABLE `visit_consultation_routes` ADD COLUMN `locked_at` TIMESTAMP NULL DEFAULT NULL",
            'locked_by' => "ALTER TABLE `visit_consultation_routes` ADD COLUMN `locked_by` BIGINT UNSIGNED NULL",
            'lock_reason' => "ALTER TABLE `visit_consultation_routes` ADD COLUMN `lock_reason` TEXT NULL",
        ], function (Blueprint $table) {
            if (! Schema::hasColumn('visit_consultation_routes', 'locked_at')) $table->timestamp('locked_at')->nullable();
            if (! Schema::hasColumn('visit_consultation_routes', 'locked_by')) $table->unsignedBigInteger('locked_by')->nullable();
            if (! Schema::hasColumn('visit_consultation_routes', 'lock_reason')) $table->text('lock_reason')->nullable();
        });
    }

    public function down(): void
    {
        $this->dropColumns('visit_consultation_routes', ['lock_reason', 'locked_by', 'locked_at']);
        $this->dropColumns('visits', ['lock_reason', 'locked_by', 'locked_at', 'completed_by', 'completed_at']);
        Schema::dropIfExists('visit_pathway_events');
    }

    private function addColumns(string $table, array $mysqlStatements, callable $sqliteCallback): void
    {
        if (! $this->tableExists($table)) {
            return;
        }

        if (DB::getDriverName() === 'sqlite') {
            Schema::table($table, $sqliteCallback);
            return;
        }

        foreach ($mysqlStatements as $column => $statement) {
            if (! $this->columnExists($table, $column)) {
                DB::statement($statement);
            }
        }
    }

    private function dropColumns(string $table, array $columns): void
    {
        if (! $this->tableExists($table)) {
            return;
        }

        if (DB::getDriverName() === 'sqlite') {
            $existing = array_values(array_filter($columns, fn (string $column) => Schema::hasColumn($table, $column)));
            if ($existing) {
                Schema::table($table, fn (Blueprint $table) => $table->dropColumn($existing));
            }
            return;
        }

        foreach ($columns as $column) {
            if ($this->columnExists($table, $column)) {
                DB::statement("ALTER TABLE `{$table}` DROP COLUMN `{$column}`");
            }
        }
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
        if (DB::getDriverName() === 'sqlite') {
            return Schema::hasColumn($table, $column);
        }

        $row = DB::selectOne(
            'SELECT COUNT(*) AS aggregate FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND COLUMN_NAME = ?',
            [DB::getDatabaseName(), $table, $column]
        );

        return (int) ($row->aggregate ?? 0) > 0;
    }
};
