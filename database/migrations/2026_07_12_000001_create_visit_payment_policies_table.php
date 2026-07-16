<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Visit Payment Policy materialisation record (Payment Timing Policy Phase 6).
 *
 * One OBSERVATIONAL record per visit. It preserves the baseline typed policy
 * (from VisitPaymentTimingResolver) SEPARATELY from a non-operational risk-based
 * recommendation. It drives NO payment gate, visit or invoice decision — there
 * is deliberately no operational/active flag. `resolved_policy` never stores
 * `inherit`. Snapshot fields are point-in-time and confidentiality-safe (no
 * free-text risk details, contact info or clinical data).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('visit_payment_policies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('visit_id')->unique()->constrained('visits')->cascadeOnDelete();

            // Baseline typed policy (authoritative baseline; never `inherit`).
            $table->string('resolved_policy')->index();
            $table->string('resolution_source')->index();
            $table->string('resolution_reason_code')->nullable();

            // Non-operational risk-based recommendation (kept separate on purpose).
            $table->string('recommended_policy')->nullable()->index();
            $table->string('recommendation_source')->nullable();
            $table->string('recommendation_reason_code')->nullable();
            $table->boolean('requires_finance_review')->default(false)->index();

            // Point-in-time policy context snapshots.
            $table->string('global_default_snapshot')->nullable();
            $table->string('visit_type_policy_snapshot')->nullable();
            $table->string('visit_type_snapshot')->nullable();
            $table->boolean('emergency_protection_snapshot')->default(false);

            // Compatible visit-wide legacy override context (identifiers only).
            $table->string('compatible_override_type_snapshot')->nullable();
            $table->string('compatible_override_scope_snapshot')->nullable();
            $table->unsignedBigInteger('compatible_override_id_snapshot')->nullable();

            // Financial-risk snapshot (identifiers/enums only — no free text).
            $table->unsignedBigInteger('patient_financial_risk_profile_id')->nullable();
            $table->string('patient_risk_level_snapshot')->nullable()->index();
            $table->string('patient_risk_status_snapshot')->nullable();
            $table->string('patient_risk_reason_snapshot')->nullable();
            $table->timestamp('patient_risk_observed_at')->nullable();

            $table->string('resolution_version');
            $table->dateTime('materialized_at')->index();
            $table->timestamp('last_refreshed_at')->nullable();

            $table->timestamps();

            // Nullable FK to the risk profile (safe short name for MySQL limits).
            $table->foreign('patient_financial_risk_profile_id', 'vpp_risk_profile_fk')
                ->references('id')->on('patient_financial_risk_profiles')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('visit_payment_policies');
    }
};
