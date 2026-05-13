<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private function hasColumn(string $table, string $column): bool
    {
        try {
            return ! empty(DB::select("SHOW COLUMNS FROM `{$table}` LIKE '{$column}'"));
        } catch (\Throwable $e) {
            return false;
        }
    }

    public function up(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            return;
        }

        if ($this->hasColumn('invoice_items', 'unit_price')) {
            DB::statement('ALTER TABLE `invoice_items` MODIFY COLUMN `unit_price` DECIMAL(10,2) NULL DEFAULT NULL');
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            return;
        }

        if ($this->hasColumn('invoice_items', 'unit_price')) {
            DB::statement('UPDATE `invoice_items` SET `unit_price` = COALESCE(`unit_price`, `selected_price`, `cash_price`, 0)');
            DB::statement('ALTER TABLE `invoice_items` MODIFY COLUMN `unit_price` DECIMAL(10,2) NOT NULL');
        }
    }
};