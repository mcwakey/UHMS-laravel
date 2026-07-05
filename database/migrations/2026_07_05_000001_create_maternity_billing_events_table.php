<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('maternity_billing_events', function (Blueprint $table) {
            $table->id();
            $table->string('mapping_key');
            $table->string('source_type', 120);
            $table->unsignedBigInteger('source_id');
            $table->foreignId('patient_id')->nullable()->constrained('patients')->nullOnDelete();
            $table->foreignId('visit_id')->nullable()->constrained('visits')->nullOnDelete();
            $table->foreignId('admission_id')->nullable()->constrained('admissions')->nullOnDelete();
            $table->foreignId('invoice_id')->nullable()->constrained('invoices')->nullOnDelete();
            $table->foreignId('invoice_item_id')->nullable()->constrained('invoice_items')->nullOnDelete();
            $table->foreignId('service_id')->nullable()->constrained('service_catalog')->nullOnDelete();
            $table->decimal('amount', 12, 2)->nullable();
            $table->string('status', 40)->default('previewed');
            $table->foreignId('posted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('posted_at')->nullable();
            $table->text('skipped_reason')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['mapping_key', 'source_type', 'source_id'], 'mat_bill_evt_source_idx');
            $table->index(['invoice_id', 'invoice_item_id'], 'mat_bill_evt_invoice_idx');
            $table->index(['status', 'posted_at'], 'mat_bill_evt_status_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('maternity_billing_events');
    }
};
