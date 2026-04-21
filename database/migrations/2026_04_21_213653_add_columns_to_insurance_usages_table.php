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
        Schema::table('insurance_usages', function (Blueprint $table) {
            $table->foreignId('patient_insurance_id')->after('id')->constrained('patient_insurances')->cascadeOnDelete();
            $table->foreignId('visit_id')->after('patient_insurance_id')->constrained()->cascadeOnDelete();
            $table->foreignId('invoice_id')->nullable()->after('visit_id')->constrained()->nullOnDelete();
            $table->decimal('amount_covered', 12, 2)->default(0)->after('invoice_id');
            $table->decimal('patient_amount', 12, 2)->default(0)->after('amount_covered');
            $table->string('reason')->nullable()->after('patient_amount');

            $table->index(['patient_insurance_id', 'visit_id']);
            $table->index('invoice_id');
        });
    }

    public function down(): void
    {
        Schema::table('insurance_usages', function (Blueprint $table) {
            $table->dropForeign(['patient_insurance_id']);
            $table->dropForeign(['visit_id']);
            $table->dropForeign(['invoice_id']);
            $table->dropColumn(['patient_insurance_id', 'visit_id', 'invoice_id', 'amount_covered', 'patient_amount', 'reason']);
        });
    }
};
