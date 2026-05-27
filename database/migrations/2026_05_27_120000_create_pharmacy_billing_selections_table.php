<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('pharmacy_billing_selections')) {
            return;
        }

        Schema::create('pharmacy_billing_selections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('visit_id')->constrained('visits')->cascadeOnDelete();
            $table->foreignId('patient_id')->constrained('patients')->cascadeOnDelete();
            $table->foreignId('prescription_id')->constrained('prescriptions')->cascadeOnDelete();
            $table->foreignId('prescription_item_id')->constrained('prescription_items')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->decimal('prescribed_quantity', 14, 4);
            $table->decimal('selected_quantity', 14, 4);
            $table->decimal('billed_quantity', 14, 4)->default(0);
            $table->decimal('dispensed_quantity', 14, 4)->default(0);
            $table->foreignId('invoice_item_id')->nullable()->constrained('invoice_items')->nullOnDelete();
            $table->foreignId('selected_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('billed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('dispensed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status', 40)->default('SELECTED');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['prescription_id', 'status'], 'pbs_prescription_status_idx');
            $table->index(['prescription_item_id', 'status'], 'pbs_item_status_idx');
            $table->index(['product_id', 'status'], 'pbs_product_status_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pharmacy_billing_selections');
    }
};