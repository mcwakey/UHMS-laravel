<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Adds product_id to invoice_items so a bill line can be attached to a
 * Product (pharmacy dispensing, consumable billing, etc.) instead of only
 * to a ServiceCatalog.
 *
 * service_catalog_id remains nullable — a line is either service-based or
 * product-based, never both.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoice_items', function (Blueprint $table) {
            // Add after service_catalog_id (which may already be nullable)
            $table->foreignId('product_id')
                ->nullable()
                ->after('service_catalog_id')
                ->constrained('products')
                ->nullOnDelete();

            $table->index('product_id');
        });
    }

    public function down(): void
    {
        Schema::table('invoice_items', function (Blueprint $table) {
            $table->dropForeign(['product_id']);
            $table->dropColumn('product_id');
        });
    }
};
