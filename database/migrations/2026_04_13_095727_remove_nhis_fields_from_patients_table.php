<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private function hasColumn(string $table, string $column): bool
    {
        if (DB::getDriverName() === 'sqlite') {
            return Schema::hasColumn($table, $column);
        }

        $result = DB::select("SHOW COLUMNS FROM `{$table}` LIKE '{$column}'");
        return !empty($result);
    }

    private function hasIndex(string $table, string $indexName): bool
    {
        if (DB::getDriverName() === 'sqlite') {
            return collect(DB::select("PRAGMA index_list('{$table}')"))
                ->contains(fn ($row) => ($row->name ?? null) === $indexName);
        }

        $result = DB::select("SHOW INDEX FROM `{$table}` WHERE Key_name = '{$indexName}'");
        return !empty($result);
    }

    public function up(): void
    {
        $hasNhis   = $this->hasColumn('patients', 'nhis_number');
        $hasExpiry = $this->hasColumn('patients', 'nhis_expiry_date');

        if (!$hasNhis && !$hasExpiry) {
            return;
        }

        if (DB::getDriverName() === 'sqlite') {
            DB::statement('DROP INDEX IF EXISTS patients_nhis_number_index');
            Schema::table('patients', function (Blueprint $table) use ($hasNhis, $hasExpiry) {
                $columns = [];
                if ($hasNhis) $columns[] = 'nhis_number';
                if ($hasExpiry) $columns[] = 'nhis_expiry_date';
                if (! empty($columns)) $table->dropColumn($columns);
            });

            return;
        }

        if ($this->hasIndex('patients', 'patients_nhis_number_index')) {
            DB::statement('ALTER TABLE `patients` DROP INDEX `patients_nhis_number_index`');
        }

        $drops = [];
        if ($hasNhis)   $drops[] = 'DROP COLUMN `nhis_number`';
        if ($hasExpiry) $drops[] = 'DROP COLUMN `nhis_expiry_date`';

        DB::statement('ALTER TABLE `patients` ' . implode(', ', $drops));
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            Schema::table('patients', function (Blueprint $table) {
                if (! Schema::hasColumn('patients', 'nhis_number')) {
                    $table->string('nhis_number', 30)->nullable();
                }
                if (! Schema::hasColumn('patients', 'nhis_expiry_date')) {
                    $table->date('nhis_expiry_date')->nullable();
                }
            });
            if (!$this->hasIndex('patients', 'patients_nhis_number_index')) {
                Schema::table('patients', function (Blueprint $table) {
                    $table->index('nhis_number', 'patients_nhis_number_index');
                });
            }

            return;
        }

        if (!$this->hasColumn('patients', 'nhis_number')) {
            DB::statement("ALTER TABLE `patients` ADD COLUMN `nhis_number` VARCHAR(30) NULL AFTER `ghana_card_number`");
        }
        if (!$this->hasColumn('patients', 'nhis_expiry_date')) {
            DB::statement("ALTER TABLE `patients` ADD COLUMN `nhis_expiry_date` DATE NULL AFTER `nhis_number`");
        }
        if (!$this->hasIndex('patients', 'patients_nhis_number_index')) {
            DB::statement('ALTER TABLE `patients` ADD INDEX `patients_nhis_number_index` (`nhis_number`)');
        }
    }
};
