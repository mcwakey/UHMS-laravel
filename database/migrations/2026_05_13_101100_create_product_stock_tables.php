<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('product_stock_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->foreignId('stock_location_id')->constrained('stock_locations')->cascadeOnDelete();
            $table->string('movement_type', 40);              // StockMovementType enum value
            $table->string('direction', 4);                   // in | out
            $table->decimal('quantity', 14, 4);
            $table->decimal('unit_cost', 12, 2)->nullable();
            $table->string('batch_no', 100)->nullable();
            $table->date('expiry_date')->nullable();
            $table->string('source_type', 100)->nullable();
            $table->unsignedBigInteger('source_id')->nullable();
            $table->foreignId('performed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('movement_date')->useCurrent();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('product_id');
            $table->index('stock_location_id');
            $table->index('movement_type');
            $table->index('direction');
            $table->index(['source_type', 'source_id']);
            $table->index('movement_date');
        });

        Schema::create('product_stock_balances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->foreignId('stock_location_id')->constrained('stock_locations')->cascadeOnDelete();
            $table->decimal('quantity_on_hand', 14, 4)->default(0);
            $table->timestamp('last_movement_at')->nullable();
            $table->timestamps();

            $table->unique(['product_id', 'stock_location_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_stock_balances');
        Schema::dropIfExists('product_stock_movements');
    }
};
