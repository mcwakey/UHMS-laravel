<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Table was already created by a prior migration in this session — skip safely.
        if (Schema::hasTable('insurance_usages')) {
            return;
        }

        Schema::create('insurance_usages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_insurance_id')->constrained('patient_insurances')->cascadeOnDelete();
            $table->foreignId('visit_id')->constrained()->cascadeOnDelete();
            $table->foreignId('invoice_id')->nullable()->constrained()->nullOnDelete();
            $table->decimal('amount_covered', 12, 2)->default(0); // insurance-paid portion
            $table->decimal('patient_amount', 12, 2)->default(0);  // patient-paid portion
            $table->string('reason')->nullable(); // why partial or cash fallback
            $table->timestamps();

            $table->index(['patient_insurance_id', 'visit_id']);
            $table->index('invoice_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('insurance_usages');
    }
};
