<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            // Cash & Carry / base price (the price a paying-cash patient is billed).
            // Insurance type/provider prices live in product_prices and override this.
            $table->decimal('base_price', 12, 2)->nullable()->after('default_cost');

            // Whether this product can appear on an invoice. Non-billable products
            // only affect stock; they never generate an InvoiceItem.
            $table->boolean('is_billable')->default(false)->after('base_price');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['base_price', 'is_billable']);
        });
    }
};
