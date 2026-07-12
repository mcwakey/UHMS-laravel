<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('visit_financial_clearances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('visit_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('status')->default('pending')->index();
            $table->string('basis')->nullable()->index();
            $table->string('operational_policy_snapshot')->nullable();
            $table->string('policy_source_snapshot')->nullable();
            $table->unsignedBigInteger('approved_arrangement_id_snapshot')->nullable();
            foreach (['patient_responsibility_snapshot', 'patient_paid_snapshot', 'patient_outstanding_snapshot', 'insurance_responsibility_snapshot', 'sponsor_responsibility_snapshot', 'corporate_responsibility_snapshot'] as $column) {
                $table->decimal($column, 15, 2)->default(0);
            }
            $table->unsignedInteger('invoice_count_snapshot')->default(0);
            $table->unsignedInteger('invoice_item_count_snapshot')->default(0);
            $table->unsignedInteger('receivable_count_snapshot')->default(0);
            $table->unsignedBigInteger('current_exception_id')->nullable();
            $table->boolean('requires_finance_action')->default(false)->index();
            $table->unsignedInteger('assessment_version')->default(1);
            $table->timestamp('assessed_at')->nullable()->index();
            $table->timestamp('cleared_at')->nullable();
            $table->timestamp('conditionally_cleared_at')->nullable();
            $table->timestamp('financially_closed_at')->nullable()->index();
            $table->timestamp('stale_at')->nullable()->index();
            $table->timestamp('reopened_at')->nullable();
            $table->timestamp('last_refreshed_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('last_refreshed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('financially_closed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('visit_financial_clearance_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('visit_financial_clearance_id')->constrained('visit_financial_clearances', 'id', 'vfch_clearance_fk')->cascadeOnDelete();
            $table->foreignId('visit_id')->constrained()->cascadeOnDelete();
            $table->string('event_type')->index();
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->string('reason_code')->nullable()->index();
            $table->foreignId('performed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('performed_at')->index();
            $table->timestamp('created_at')->useCurrent();
        });

        Schema::create('visit_financial_clearance_exceptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('visit_id')->constrained()->cascadeOnDelete();
            $table->foreignId('visit_financial_clearance_id')->nullable()->constrained('visit_financial_clearances', 'id', 'vfce_clearance_fk')->nullOnDelete();
            $table->string('type')->index();
            $table->string('status')->default('pending')->index();
            $table->decimal('requested_amount', 15, 2);
            $table->decimal('approved_amount', 15, 2)->nullable();
            $table->string('reason_code')->nullable();
            $table->string('request_reason', 1000);
            $table->string('supporting_reference', 191)->nullable();
            $table->foreignId('requested_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('requested_at');
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
            $table->unsignedBigInteger('replaced_by_exception_id')->nullable();
            $table->json('financial_summary_snapshot');
            $table->string('payment_policy_snapshot')->nullable();
            $table->unsignedBigInteger('arrangement_id_snapshot')->nullable();
            $table->string('risk_level_snapshot')->nullable();
            $table->timestamps();
            $table->index(['visit_id', 'status']);
            $table->foreign('replaced_by_exception_id', 'vfce_replaced_fk')->references('id')->on('visit_financial_clearance_exceptions')->nullOnDelete();
        });

        Schema::create('visit_financial_clearance_exception_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('visit_financial_clearance_exception_id')->constrained('visit_financial_clearance_exceptions', 'id', 'vfceh_exception_fk')->cascadeOnDelete();
            $table->foreignId('visit_id')->constrained()->cascadeOnDelete();
            $table->string('event_type')->index();
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->foreignId('performed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('performed_at')->index();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('visit_financial_clearance_exception_history');
        Schema::dropIfExists('visit_financial_clearance_exceptions');
        Schema::dropIfExists('visit_financial_clearance_history');
        Schema::dropIfExists('visit_financial_clearances');
    }
};
