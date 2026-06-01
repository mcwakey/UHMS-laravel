<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Credit notes & write-offs.
 *
 * A credit note records a non-cash reduction of an invoice balance:
 *   - type = credit_note  → goodwill / billing-correction credit
 *   - type = write_off    → bad-debt write-off (uncollectable balance)
 *
 * Issuing a credit note reduces the patient's outstanding balance on the
 * invoice without taking money. It is fully audited and reversible.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('credit_notes', function (Blueprint $table) {
            $table->id();
            $table->string('credit_note_number')->unique();
            $table->foreignId('invoice_id')->constrained('invoices')->cascadeOnDelete();
            $table->foreignId('patient_id')->nullable()->constrained('patients')->nullOnDelete();
            $table->string('type', 20)->default('credit_note'); // credit_note | write_off
            $table->string('status', 20)->default('issued');     // issued | cancelled
            $table->decimal('amount', 12, 2)->default(0);
            $table->string('reason');
            $table->text('notes')->nullable();
            $table->foreignId('issued_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('cancelled_at')->nullable();
            $table->foreignId('cancelled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('cancellation_reason', 500)->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['invoice_id', 'status']);
            $table->index(['type', 'status']);
            $table->index('patient_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('credit_notes');
    }
};
