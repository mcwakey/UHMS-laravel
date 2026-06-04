<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoice_discounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->constrained('invoices')->cascadeOnDelete();
            $table->foreignId('invoice_item_id')->constrained('invoice_items')->cascadeOnDelete();
            $table->string('action', 32);
            $table->decimal('line_total', 12, 2);
            $table->decimal('old_discount_amount', 12, 2)->default(0);
            $table->decimal('new_discount_amount', 12, 2)->default(0);
            $table->decimal('old_patient_payable', 12, 2)->default(0);
            $table->decimal('new_patient_payable', 12, 2)->default(0);
            $table->decimal('old_balance', 12, 2)->default(0);
            $table->decimal('new_balance', 12, 2)->default(0);
            $table->boolean('is_override')->default(false);
            $table->text('reason');
            $table->foreignId('performed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('performed_at');
            $table->timestamps();

            $table->index(['invoice_id', 'performed_at']);
            $table->index(['invoice_item_id', 'performed_at']);
            $table->index(['performed_by', 'performed_at']);
            $table->index(['is_override', 'performed_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoice_discounts');
    }
};
