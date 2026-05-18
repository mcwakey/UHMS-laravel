<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('goods_received_notes', function (Blueprint $table) {
            $table->id();
            $table->string('grn_number', 50)->unique();
            $table->foreignId('purchase_order_id')->constrained('purchase_orders')->cascadeOnDelete();
            $table->foreignId('supplier_id')->nullable()->constrained('suppliers')->nullOnDelete();
            $table->timestamp('received_date');
            $table->string('supplier_delivery_no', 100)->nullable();
            $table->foreignId('received_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('purchase_order_id');
            $table->index('supplier_id');
            $table->index('received_date');
        });

        Schema::create('goods_received_note_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('goods_received_note_id')->constrained('goods_received_notes')->cascadeOnDelete();
            $table->foreignId('purchase_order_item_id')->nullable()->constrained('purchase_order_items')->nullOnDelete();
            $table->foreignId('product_id')->nullable()->constrained('products')->nullOnDelete();
            $table->foreignId('stock_location_id')->constrained('stock_locations')->cascadeOnDelete();
            $table->decimal('quantity_received', 14, 4);
            $table->decimal('unit_cost', 12, 2)->nullable();
            $table->string('batch_no', 100)->nullable();
            $table->date('expiry_date')->nullable();
            // We log both ledger movement IDs because Phase 1 still has a split ledger.
            $table->unsignedBigInteger('product_stock_movement_id')->nullable();
            $table->unsignedBigInteger('stock_movement_id')->nullable();
            $table->timestamps();

            $table->index('goods_received_note_id');
            $table->index('purchase_order_item_id');
            $table->index('product_id');
            $table->index('stock_location_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('goods_received_note_items');
        Schema::dropIfExists('goods_received_notes');
    }
};
