<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Per-visit payment arrangements — request/approval workflow (Payment Timing
 * Policy Phase 7).
 *
 * A dedicated table (NOT VisitBillingOverride, whose semantics differ). An
 * approved arrangement is an ADMINISTRATIVE record only: it drives no payment
 * gate, invoice or visit change in Phase 7. There is deliberately no
 * `is_operational` flag. Uniqueness (one pending + one current approved per
 * visit) is enforced in the service under row locks; requested/approved policies
 * never store `inherit`.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('visit_payment_arrangements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('visit_id')->constrained('visits')->cascadeOnDelete();
            $table->unsignedBigInteger('visit_payment_policy_id')->nullable();

            $table->string('requested_policy')->index();
            $table->string('approved_policy')->nullable()->index();

            $table->string('source')->default('manual_request');
            $table->string('status')->default('pending')->index();

            $table->string('request_reason_code')->nullable();
            $table->string('request_reason', 1000)->nullable();
            $table->string('supporting_reference', 191)->nullable();

            $table->foreignId('requested_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('requested_at')->nullable();

            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->string('review_decision_reason', 1000)->nullable();

            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();

            $table->date('effective_from')->nullable();
            $table->date('expires_at')->nullable()->index();

            $table->foreignId('withdrawn_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('withdrawn_at')->nullable();
            $table->string('withdrawal_reason', 1000)->nullable();

            $table->foreignId('revoked_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('revoked_at')->nullable();
            $table->string('revocation_reason', 1000)->nullable();

            $table->unsignedBigInteger('replaced_by_arrangement_id')->nullable();

            $table->boolean('requires_approval')->default(true);
            $table->boolean('requires_separate_approver')->default(false);
            $table->string('risk_level_snapshot')->nullable()->index();
            $table->string('risk_status_snapshot')->nullable();
            $table->string('baseline_policy_snapshot')->nullable();
            $table->string('recommended_policy_snapshot')->nullable();
            $table->boolean('finance_review_snapshot')->default(false);

            $table->timestamps();

            $table->foreign('visit_payment_policy_id', 'vpa_policy_fk')
                ->references('id')->on('visit_payment_policies')->nullOnDelete();
            $table->foreign('replaced_by_arrangement_id', 'vpa_replaced_by_fk')
                ->references('id')->on('visit_payment_arrangements')->nullOnDelete();

            $table->index(['visit_id', 'status']);
            $table->index('requested_by');
            $table->index('approved_by');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('visit_payment_arrangements');
    }
};
