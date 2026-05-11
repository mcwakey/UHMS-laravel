<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private function hasColumn(string $table, string $column): bool
    {
        $result = DB::select("SHOW COLUMNS FROM `{$table}` LIKE '{$column}'");
        return !empty($result);
    }

    private function hasIndex(string $table, string $indexName): bool
    {
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
