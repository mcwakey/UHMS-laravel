<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * GP-1: allow purchase_order_items to reference a generic Product
 * (in addition to drug_id and investigation_item_id).
 *
 * After this migration, a PO line carries exactly one of:
 *   - drug_id            (item_type = 'drug')
 *   - investigation_item_id (item_type = 'investigation')
 *   - product_id         (item_type = 'product')
 *
 * The drug_id column is made nullable so product/investigation lines do not
 * need a dummy drug. Backfill is unnecessary — existing rows already have
 * drug_id populated.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('purchase_order_items', function (Blueprint $table) {
            if (! $this->columnExists('purchase_order_items', 'product_id')) {
                $table->foreignId('product_id')
                    ->nullable()
                    ->after('investigation_item_id')
                    ->constrained('products')
                    ->nullOnDelete();
            }
        });

        // Make drug_id nullable so non-drug lines (product / investigation) can omit it.
        // Use a raw statement because doctrine/dbal may not be installed and the host
        // MariaDB chokes on Laravel 12's schema introspector.
        try {
            DB::statement('ALTER TABLE purchase_order_items MODIFY drug_id BIGINT UNSIGNED NULL');
        } catch (\Throwable $e) {
            // Already nullable or driver doesn't support: ignore.
        }
    }

    public function down(): void
    {
        Schema::table('purchase_order_items', function (Blueprint $table) {
            if ($this->columnExists('purchase_order_items', 'product_id')) {
                $table->dropForeign(['product_id']);
                $table->dropColumn('product_id');
            }
        });

        try {
            DB::statement('ALTER TABLE purchase_order_items MODIFY drug_id BIGINT UNSIGNED NOT NULL');
        } catch (\Throwable $e) {
            // ignore
        }
    }

    private function columnExists(string $table, string $column): bool
    {
        $db = DB::connection()->getDatabaseName();
        $row = DB::selectOne(
            'SELECT COUNT(*) AS c FROM information_schema.columns WHERE table_schema = ? AND table_name = ? AND column_name = ?',
            [$db, $table, $column]
        );

        return (int) ($row->c ?? 0) > 0;
    }
};
