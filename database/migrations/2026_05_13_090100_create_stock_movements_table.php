<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('stock_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('drug_id')->constrained('drugs')->cascadeOnDelete();
            $table->foreignId('stock_location_id')->constrained('stock_locations')->cascadeOnDelete();

            $table->string('movement_type', 40);   // see StockMovementType
            $table->string('direction', 4);        // in | out
            $table->decimal('quantity', 14, 4);    // absolute (positive) quantity
            $table->decimal('unit_cost', 12, 2)->nullable();

            $table->string('batch_no', 100)->nullable();
            $table->date('expiry_date')->nullable();

            // Polymorphic source (purchase_order_item, prescription_item, stock_transfer_item, etc.)
            $table->string('source_type', 100)->nullable();
            $table->unsignedBigInteger('source_id')->nullable();

            $table->foreignId('performed_by')->nullable()
                ->constrained('users')->nullOnDelete();
            $table->timestamp('movement_date')->useCurrent();
            $table->text('notes')->nullable();

            $table->timestamps();

            $table->index('drug_id');
            $table->index('stock_location_id');
            $table->index('movement_type');
            $table->index('direction');
            $table->index(['source_type', 'source_id']);
            $table->index('movement_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_movements');
    }
};
