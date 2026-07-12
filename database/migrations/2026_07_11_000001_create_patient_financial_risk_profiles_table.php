<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Patient Financial-Risk Profiles (Payment Timing Policy Phase 5).
 *
 * A controlled administrative classification of the hospital's willingness to
 * extend payment flexibility to a patient. This is NOT invoice/receivable state
 * and drives NO payment gate or visit policy in Phase 5. Only one profile per
 * patient may occupy the active slot (active / under_review) — enforced in the
 * service layer with row locking (see PatientFinancialRiskService).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('patient_financial_risk_profiles', function (Blueprint $table) {
            $table->id();

            $table->foreignId('patient_id')->constrained('patients')->cascadeOnDelete();

            // App\Enums\PatientFinancialRiskLevel
            $table->string('risk_level')->default('normal')->index();
            // App\Enums\PatientFinancialRiskReason
            $table->string('primary_reason')->nullable();
            $table->string('reason_details', 1000)->nullable();
            // App\Enums\PatientFinancialRiskStatus
            $table->string('status')->default('active')->index();

            // Informational only in Phase 5 — never enforced against invoices/visits.
            $table->decimal('credit_limit', 10, 2)->nullable();

            $table->date('effective_from');
            $table->date('review_due_at')->nullable()->index();
            $table->date('expires_at')->nullable()->index();

            $table->string('reference', 191)->nullable();

            $table->foreignId('set_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->foreignId('suspended_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('suspended_at')->nullable();
            $table->foreignId('cleared_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('cleared_at')->nullable();
            $table->string('clearance_reason', 1000)->nullable();

            $table->timestamps();

            $table->index(['patient_id', 'status']);
            $table->index(['status', 'risk_level']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('patient_financial_risk_profiles');
    }
};
