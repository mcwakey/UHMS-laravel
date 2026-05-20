<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private function cols(string $table): array
    {
        if (DB::getDriverName() === 'sqlite') {
            return Schema::getColumnListing($table);
        }

        return array_map(fn ($r) => $r->Field, DB::select("SHOW COLUMNS FROM `{$table}`"));
    }

    private function hasIndex(string $table, string $name): bool
    {
        if (DB::getDriverName() === 'sqlite') {
            return collect(DB::select("PRAGMA index_list('{$table}')"))
                ->contains(fn ($row) => ($row->name ?? null) === $name);
        }

        return !empty(DB::select("SHOW INDEX FROM `{$table}` WHERE Key_name = '{$name}'"));
    }

    public function up(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            $this->upSqlite();
            return;
        }

        // 1. departments — add result_type + is_stock_managed
        $depts = $this->cols('departments');
        $adds = [];
        if (!in_array('result_type', $depts))      $adds[] = "ADD COLUMN `result_type` VARCHAR(191) NOT NULL DEFAULT 'none' AFTER `type`";
        if (!in_array('is_stock_managed', $depts))  $adds[] = 'ADD COLUMN `is_stock_managed` TINYINT(1) NOT NULL DEFAULT 0 AFTER `result_type`';
        if (!empty($adds)) DB::statement('ALTER TABLE `departments` ' . implode(', ', $adds));

        // 2. lab_requests — add target_department_id FK
        $lr = $this->cols('lab_requests');
        if (!in_array('target_department_id', $lr)) {
            DB::statement("ALTER TABLE `lab_requests` ADD COLUMN `target_department_id` BIGINT UNSIGNED NULL AFTER `department_id`");
            DB::statement('ALTER TABLE `lab_requests` ADD CONSTRAINT `lab_requests_target_department_id_foreign` FOREIGN KEY (`target_department_id`) REFERENCES `departments` (`id`) ON DELETE SET NULL');
        }
        if (!$this->hasIndex('lab_requests', 'lab_requests_target_department_id_index')) {
            DB::statement('CREATE INDEX `lab_requests_target_department_id_index` ON `lab_requests` (`target_department_id`)');
        }

        // 3. lab_request_items — make lab_test_id nullable (MODIFY COLUMN, not change())
        $lriRow = collect(DB::select('SHOW COLUMNS FROM `lab_request_items`'))->firstWhere('Field', 'lab_test_id');
        if ($lriRow && $lriRow->Null === 'NO') {
            // Drop FK first, modify, re-add FK
            try { DB::statement('ALTER TABLE `lab_request_items` DROP FOREIGN KEY `lab_request_items_lab_test_id_foreign`'); } catch (\Throwable $e) {}
            DB::statement('ALTER TABLE `lab_request_items` MODIFY COLUMN `lab_test_id` BIGINT UNSIGNED NULL');
            try { DB::statement('ALTER TABLE `lab_request_items` ADD CONSTRAINT `lab_request_items_lab_test_id_foreign` FOREIGN KEY (`lab_test_id`) REFERENCES `lab_tests` (`id`) ON DELETE SET NULL'); } catch (\Throwable $e) {}
        }

        // 4. lab_results — make lab_request_item_id nullable + add columns
        $lrRes = $this->cols('lab_results');
        $lrResRow = collect(DB::select('SHOW COLUMNS FROM `lab_results`'))->firstWhere('Field', 'lab_request_item_id');
        if ($lrResRow && $lrResRow->Null === 'NO') {
            try { DB::statement('ALTER TABLE `lab_results` DROP FOREIGN KEY `lab_results_lab_request_item_id_foreign`'); } catch (\Throwable $e) {}
            DB::statement('ALTER TABLE `lab_results` MODIFY COLUMN `lab_request_item_id` BIGINT UNSIGNED NULL');
            try { DB::statement('ALTER TABLE `lab_results` ADD CONSTRAINT `lab_results_lab_request_item_id_foreign` FOREIGN KEY (`lab_request_item_id`) REFERENCES `lab_request_items` (`id`) ON DELETE SET NULL'); } catch (\Throwable $e) {}
        }
        $resAdds = [];
        if (!in_array('result_type', $lrRes))      $resAdds[] = "ADD COLUMN `result_type` VARCHAR(191) NOT NULL DEFAULT 'parameters' AFTER `lab_request_id`";
        if (!in_array('result_text', $lrRes))      $resAdds[] = 'ADD COLUMN `result_text` LONGTEXT NULL AFTER `result_value`';
        if (!in_array('result_file', $lrRes))      $resAdds[] = 'ADD COLUMN `result_file` VARCHAR(191) NULL AFTER `result_text`';
        if (!in_array('result_file_name', $lrRes)) $resAdds[] = 'ADD COLUMN `result_file_name` VARCHAR(191) NULL AFTER `result_file`';
        if (!empty($resAdds)) DB::statement('ALTER TABLE `lab_results` ' . implode(', ', $resAdds));
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            $this->downSqlite();
            return;
        }

        // Drop result columns from lab_results
        $lrRes = $this->cols('lab_results');
        $toDrop = array_filter(['result_type','result_text','result_file','result_file_name'], fn ($c) => in_array($c, $lrRes));
        if (!empty($toDrop)) DB::statement('ALTER TABLE `lab_results` ' . implode(', ', array_map(fn ($c) => "DROP COLUMN `{$c}`", $toDrop)));

        // Drop lab_requests FK and column
        try { DB::statement('ALTER TABLE `lab_requests` DROP FOREIGN KEY `lab_requests_target_department_id_foreign`'); } catch (\Throwable $e) {}
        if ($this->hasIndex('lab_requests', 'lab_requests_target_department_id_index')) {
            DB::statement('DROP INDEX `lab_requests_target_department_id_index` ON `lab_requests`');
        }
        $lr = $this->cols('lab_requests');
        if (in_array('target_department_id', $lr)) DB::statement('ALTER TABLE `lab_requests` DROP COLUMN `target_department_id`');

        // Drop departments columns
        $depts = $this->cols('departments');
        $dropDepts = array_filter(['result_type','is_stock_managed'], fn ($c) => in_array($c, $depts));
        if (!empty($dropDepts)) DB::statement('ALTER TABLE `departments` ' . implode(', ', array_map(fn ($c) => "DROP COLUMN `{$c}`", $dropDepts)));
    }

    private function upSqlite(): void
    {
        Schema::table('departments', function (Blueprint $table) {
            if (! Schema::hasColumn('departments', 'result_type')) {
                $table->string('result_type')->default('none');
            }
            if (! Schema::hasColumn('departments', 'is_stock_managed')) {
                $table->boolean('is_stock_managed')->default(false);
            }
        });

        if (! Schema::hasColumn('lab_requests', 'target_department_id')) {
            Schema::table('lab_requests', function (Blueprint $table) {
                $table->foreignId('target_department_id')->nullable()->constrained('departments')->nullOnDelete();
            });
        }

        Schema::table('lab_results', function (Blueprint $table) {
            if (! Schema::hasColumn('lab_results', 'result_type')) {
                $table->string('result_type')->default('parameters');
            }
            if (! Schema::hasColumn('lab_results', 'result_text')) {
                $table->longText('result_text')->nullable();
            }
            if (! Schema::hasColumn('lab_results', 'result_file')) {
                $table->string('result_file')->nullable();
            }
            if (! Schema::hasColumn('lab_results', 'result_file_name')) {
                $table->string('result_file_name')->nullable();
            }
        });
    }

    private function downSqlite(): void
    {
        Schema::table('lab_results', function (Blueprint $table) {
            foreach (['result_type', 'result_text', 'result_file', 'result_file_name'] as $column) {
                if (Schema::hasColumn('lab_results', $column)) {
                    $table->dropColumn($column);
                }
            }
        });

        if (Schema::hasColumn('lab_requests', 'target_department_id')) {
            Schema::table('lab_requests', function (Blueprint $table) {
                $table->dropConstrainedForeignId('target_department_id');
            });
        }

        Schema::table('departments', function (Blueprint $table) {
            foreach (['result_type', 'is_stock_managed'] as $column) {
                if (Schema::hasColumn('departments', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
