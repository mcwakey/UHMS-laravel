<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('consumable_usages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('visit_id')->nullable()->constrained('visits')->nullOnDelete();
            $table->foreignId('patient_id')->nullable()->constrained('patients')->nullOnDelete();
            $table->foreignId('service_id')->nullable()->constrained('service_catalog')->nullOnDelete();
            $table->string('source_type', 100);                              // procedure_request, investigation_result, ward_care, etc.
            $table->unsignedBigInteger('source_id');
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->foreignId('stock_location_id')->constrained('stock_locations')->cascadeOnDelete();
            $table->decimal('quantity_used', 14, 4);
            $table->foreignId('stock_movement_id')->nullable();              // product_stock_movements.id (no FK to avoid hard coupling)
            $table->foreignId('used_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('used_at')->useCurrent();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['source_type', 'source_id']);
            $table->index('product_id');
            $table->index('service_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('consumable_usages');
    }
};
