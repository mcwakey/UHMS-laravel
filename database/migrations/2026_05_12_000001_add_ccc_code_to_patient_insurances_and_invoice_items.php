<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Add CCC code to patient_insurances. Also ensure invoice_items has the
 * simplified pricing columns (selected_price, cash_price already added earlier;
 * this guards in case the table is missing any). No destructive drops here.
 */
return new class extends Migration {

    private function hasColumn(string $table, string $col): bool
    {
        try {
            return ! empty(DB::select("SHOW COLUMNS FROM `{$table}` LIKE '{$col}'"));
        } catch (\Throwable $e) {
            return false;
        }
    }

    public function up(): void
    {
        if (DB::getDriverName() === 'sqlite') return;

        // 1. patient_insurances.ccc_code
        if (! $this->hasColumn('patient_insurances', 'ccc_code')) {
            DB::statement("ALTER TABLE `patient_insurances` ADD COLUMN `ccc_code` VARCHAR(64) NULL AFTER `policy_number`");
            try { DB::statement('CREATE INDEX `patient_insurances_ccc_code_index` ON `patient_insurances` (`ccc_code`)'); } catch (\Throwable $e) {}
        }

        // 2. invoice_items \u2014 ensure required simplified columns exist (safety net).
        $adds = [];
        if (! $this->hasColumn('invoice_items', 'selected_price'))  $adds[] = 'ADD COLUMN `selected_price` DECIMAL(12,2) NULL';
        if (! $this->hasColumn('invoice_items', 'cash_price'))      $adds[] = 'ADD COLUMN `cash_price` DECIMAL(12,2) NULL';
        if (! $this->hasColumn('invoice_items', 'patient_payable')) $adds[] = 'ADD COLUMN `patient_payable` DECIMAL(12,2) NULL';
        if (! $this->hasColumn('invoice_items', 'paid_amount'))     $adds[] = 'ADD COLUMN `paid_amount` DECIMAL(12,2) NOT NULL DEFAULT 0';
        if (! $this->hasColumn('invoice_items', 'balance'))         $adds[] = 'ADD COLUMN `balance` DECIMAL(12,2) NOT NULL DEFAULT 0';
        if (! empty($adds)) {
            try { DB::statement('ALTER TABLE `invoice_items` ' . implode(', ', $adds)); } catch (\Throwable $e) {}
        }

        // 3. Backfill: selected_price = unit_price where null.
        if ($this->hasColumn('invoice_items', 'selected_price') && $this->hasColumn('invoice_items', 'unit_price')) {
            DB::statement('UPDATE `invoice_items` SET `selected_price` = `unit_price` WHERE `selected_price` IS NULL');
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'sqlite') return;

        if ($this->hasColumn('patient_insurances', 'ccc_code')) {
            try { DB::statement('DROP INDEX `patient_insurances_ccc_code_index` ON `patient_insurances`'); } catch (\Throwable $e) {}
            DB::statement('ALTER TABLE `patient_insurances` DROP COLUMN `ccc_code`');
        }
    }
};
