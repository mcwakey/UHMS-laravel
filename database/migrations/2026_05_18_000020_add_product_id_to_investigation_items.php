<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 4 (unified inventory): link investigation_items into the products table.
 *
 *   - investigation_items.product_id    nullable FK → products.id
 *
 * Investigation items continue to exist as a catalog (lab-specific metadata
 * like reorder_level, category enum, etc.) but they now mirror a row in
 * `products` so all stock flows can write to the unified
 * `product_stock_movements` / `product_stock_balances` ledger instead of the
 * legacy `investigation_item_stock` table.
 *
 * Backfill is handled by `inventory:link-investigation-items-to-products`.
 */
return new class extends Migration {
    public function up(): void
    {
        $has = collect(DB::select("SHOW COLUMNS FROM investigation_items LIKE 'product_id'"))->isNotEmpty();
        if (! $has) {
            Schema::table('investigation_items', function (Blueprint $table) {
                $table->unsignedBigInteger('product_id')->nullable()->after('id');
                $table->foreign('product_id')->references('id')->on('products')->nullOnDelete();
                $table->index('product_id');
            });
        }
    }

    public function down(): void
    {
        $has = collect(DB::select("SHOW COLUMNS FROM investigation_items LIKE 'product_id'"))->isNotEmpty();
        if ($has) {
            Schema::table('investigation_items', function (Blueprint $table) {
                try { $table->dropForeign(['product_id']); } catch (\Throwable $e) {}
                try { $table->dropIndex(['product_id']); }   catch (\Throwable $e) {}
                $table->dropColumn('product_id');
            });
        }
    }
};
