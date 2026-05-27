<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('consumable_usages')) {
            return;
        }

        if (DB::getDriverName() === 'sqlite') {
            Schema::table('consumable_usages', function (Blueprint $table) {
                if (! Schema::hasColumn('consumable_usages', 'is_billable')) {
                    $table->boolean('is_billable')->default(false)->after('quantity_used');
                }
                if (! Schema::hasColumn('consumable_usages', 'invoice_item_id')) {
                    $table->foreignId('invoice_item_id')->nullable()->after('is_billable')->constrained('invoice_items')->nullOnDelete();
                }
            });

            return;
        }

        if ($this->missingColumn('is_billable')) {
            DB::statement('ALTER TABLE `consumable_usages` ADD COLUMN `is_billable` TINYINT(1) NOT NULL DEFAULT 0 AFTER `quantity_used`');
        }

        if ($this->missingColumn('invoice_item_id')) {
            DB::statement('ALTER TABLE `consumable_usages` ADD COLUMN `invoice_item_id` BIGINT UNSIGNED NULL AFTER `is_billable`');
            DB::statement('ALTER TABLE `consumable_usages` ADD CONSTRAINT `cu_invoice_item_id_foreign` FOREIGN KEY (`invoice_item_id`) REFERENCES `invoice_items`(`id`) ON DELETE SET NULL');
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('consumable_usages')) {
            return;
        }

        if (DB::getDriverName() === 'sqlite') {
            Schema::table('consumable_usages', function (Blueprint $table) {
                if (Schema::hasColumn('consumable_usages', 'invoice_item_id')) {
                    $table->dropConstrainedForeignId('invoice_item_id');
                }
                if (Schema::hasColumn('consumable_usages', 'is_billable')) {
                    $table->dropColumn('is_billable');
                }
            });

            return;
        }

        if (! $this->missingColumn('invoice_item_id')) {
            DB::statement('ALTER TABLE `consumable_usages` DROP FOREIGN KEY `cu_invoice_item_id_foreign`');
            DB::statement('ALTER TABLE `consumable_usages` DROP COLUMN `invoice_item_id`');
        }

        if (! $this->missingColumn('is_billable')) {
            DB::statement('ALTER TABLE `consumable_usages` DROP COLUMN `is_billable`');
        }
    }

    private function missingColumn(string $column): bool
    {
        return empty(DB::select("SHOW COLUMNS FROM `consumable_usages` LIKE '{$column}'"));
    }
};