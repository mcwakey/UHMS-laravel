<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Immutable history of patient financial-risk profile transitions (Payment
 * Timing Policy Phase 5). Rows are append-only — never edited or deleted through
 * the application. old_values/new_values store only material fields (level,
 * status, dates, reason) — never full patient snapshots or personal data.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('patient_financial_risk_history', function (Blueprint $table) {
            $table->id();

            // Safely shortened FK/index names to stay within MySQL's 64-char identifier limit.
            $table->unsignedBigInteger('patient_financial_risk_profile_id');
            $table->foreign('patient_financial_risk_profile_id', 'pfr_history_profile_fk')
                ->references('id')->on('patient_financial_risk_profiles')->cascadeOnDelete();
            $table->index('patient_financial_risk_profile_id', 'pfr_history_profile_idx');

            $table->foreignId('patient_id')->constrained('patients')->cascadeOnDelete();

            // App\Enums\PatientFinancialRiskEvent
            $table->string('event_type')->index();
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->string('reason', 1000)->nullable();

            $table->foreignId('performed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('performed_at');

            $table->timestamps();

            $table->index(['patient_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('patient_financial_risk_history');
    }
};
