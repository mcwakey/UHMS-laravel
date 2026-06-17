<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('receivable_cases', function (Blueprint $table) {
            $table->id();
            $table->string('case_number')->unique();
            $table->string('payer_type', 30)->default('unknown');
            $table->unsignedBigInteger('payer_id')->nullable();
            $table->string('payer_name_snapshot')->nullable();
            $table->foreignId('patient_id')->nullable()->constrained('patients')->nullOnDelete();
            $table->foreignId('insurance_provider_id')->nullable()->constrained('insurance_providers')->nullOnDelete();
            $table->foreignId('sponsor_id')->nullable()->constrained('sponsors')->nullOnDelete();
            $table->foreignId('corporate_client_id')->nullable()->constrained('corporate_clients')->nullOnDelete();
            $table->foreignId('claim_id')->nullable()->constrained('claims')->nullOnDelete();
            $table->string('case_type', 40)->default('normal_collection');
            $table->string('priority', 20)->default('normal');
            $table->string('status', 40)->default('open');
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('opened_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('opened_at')->nullable();
            $table->foreignId('closed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('closed_at')->nullable();
            $table->string('closure_reason')->nullable();
            $table->decimal('total_original_amount', 18, 2)->default(0);
            $table->decimal('total_outstanding_amount', 18, 2)->default(0);
            $table->decimal('total_disputed_amount', 18, 2)->default(0);
            $table->decimal('total_promised_amount', 18, 2)->default(0);
            $table->date('oldest_due_date')->nullable();
            $table->string('aging_bucket', 20)->default('current');
            $table->longText('metadata_snapshot')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['payer_type', 'payer_id'], 'recv_case_payer_idx');
            $table->index(['status', 'assigned_to'], 'recv_case_status_assignee_idx');
            $table->index(['aging_bucket', 'priority'], 'recv_case_aging_priority_idx');
        });

        Schema::create('receivable_case_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('receivable_case_id')->constrained('receivable_cases')->cascadeOnDelete();
            $table->string('source_type', 120);
            $table->unsignedBigInteger('source_id');
            $table->foreignId('invoice_id')->nullable()->constrained('invoices')->nullOnDelete();
            $table->string('invoice_number')->nullable();
            $table->foreignId('claim_id')->nullable()->constrained('claims')->nullOnDelete();
            $table->string('payer_type', 30);
            $table->unsignedBigInteger('payer_id')->nullable();
            $table->decimal('original_amount', 18, 2)->default(0);
            $table->decimal('outstanding_amount', 18, 2)->default(0);
            $table->decimal('disputed_amount', 18, 2)->default(0);
            $table->decimal('promised_amount', 18, 2)->default(0);
            $table->date('due_date')->nullable();
            $table->string('aging_bucket', 20)->default('current');
            $table->string('status', 30)->default('open');
            $table->longText('metadata_snapshot')->nullable();
            $table->timestamps();

            $table->index(['source_type', 'source_id'], 'recv_item_source_idx');
            $table->index(['receivable_case_id', 'status'], 'recv_item_case_status_idx');
        });

        Schema::create('receivable_followups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('receivable_case_id')->constrained('receivable_cases')->cascadeOnDelete();
            $table->string('followup_type', 30);
            $table->date('followup_date');
            $table->date('next_followup_date')->nullable();
            $table->string('contact_person')->nullable();
            $table->string('contact_channel')->nullable();
            $table->text('summary');
            $table->string('outcome', 40)->default('other');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['receivable_case_id', 'followup_date'], 'recv_follow_case_date_idx');
        });

        Schema::create('receivable_promises', function (Blueprint $table) {
            $table->id();
            $table->foreignId('receivable_case_id')->constrained('receivable_cases')->cascadeOnDelete();
            $table->string('promised_by')->nullable();
            $table->date('promise_date');
            $table->date('expected_payment_date');
            $table->decimal('promised_amount', 18, 2);
            $table->string('status', 30)->default('active');
            $table->decimal('fulfilled_amount', 18, 2)->default(0);
            $table->dateTime('fulfilled_at')->nullable();
            $table->dateTime('broken_at')->nullable();
            $table->string('broken_reason')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['status', 'expected_payment_date'], 'recv_promise_status_date_idx');
        });

        Schema::create('receivable_disputes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('receivable_case_id')->constrained('receivable_cases')->cascadeOnDelete();
            $table->string('source_type', 120)->nullable();
            $table->unsignedBigInteger('source_id')->nullable();
            $table->string('dispute_reason');
            $table->decimal('disputed_amount', 18, 2);
            $table->string('status', 40)->default('open');
            $table->foreignId('raised_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('raised_at')->nullable();
            $table->foreignId('resolved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('resolved_at')->nullable();
            $table->text('resolution_note')->nullable();
            $table->string('recommended_action', 40)->nullable();
            $table->longText('metadata_snapshot')->nullable();
            $table->timestamps();

            $table->index(['status', 'recommended_action'], 'recv_dispute_status_action_idx');
        });

        Schema::create('receivable_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('receivable_case_id')->constrained('receivable_cases')->cascadeOnDelete();
            $table->foreignId('assigned_to')->constrained('users')->cascadeOnDelete();
            $table->foreignId('assigned_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('assigned_at');
            $table->dateTime('released_at')->nullable();
            $table->string('release_reason')->nullable();
            $table->timestamps();

            $table->index(['receivable_case_id', 'released_at'], 'recv_assign_active_idx');
        });

        Schema::create('receivable_dunning_notices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('receivable_case_id')->constrained('receivable_cases')->cascadeOnDelete();
            $table->string('notice_number')->unique();
            $table->string('notice_level', 40);
            $table->date('notice_date');
            $table->string('delivery_channel', 30)->default('print');
            $table->string('recipient_name')->nullable();
            $table->string('recipient_contact')->nullable();
            $table->string('subject');
            $table->longText('body');
            $table->string('status', 30)->default('generated');
            $table->foreignId('generated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('generated_at')->nullable();
            $table->foreignId('sent_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('sent_at')->nullable();
            $table->longText('metadata_snapshot')->nullable();
            $table->timestamps();

            $table->index(['receivable_case_id', 'notice_level'], 'recv_dunning_case_level_idx');
        });

        Schema::create('receivable_statement_runs', function (Blueprint $table) {
            $table->id();
            $table->string('statement_number')->unique();
            $table->string('payer_type', 30);
            $table->unsignedBigInteger('payer_id')->nullable();
            $table->string('payer_name_snapshot')->nullable();
            $table->date('period_start');
            $table->date('period_end');
            $table->string('status', 30)->default('generated');
            $table->decimal('opening_balance', 18, 2)->default(0);
            $table->decimal('charges', 18, 2)->default(0);
            $table->decimal('payments', 18, 2)->default(0);
            $table->decimal('credit_notes', 18, 2)->default(0);
            $table->decimal('writeoffs', 18, 2)->default(0);
            $table->decimal('closing_balance', 18, 2)->default(0);
            $table->foreignId('generated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('generated_at')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('approved_at')->nullable();
            $table->longText('metadata_snapshot')->nullable();
            $table->timestamps();

            $table->index(['payer_type', 'payer_id'], 'recv_stmt_payer_idx');
            $table->index(['period_start', 'period_end'], 'recv_stmt_period_idx');
        });

        Schema::create('receivable_statement_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('receivable_statement_run_id')->constrained('receivable_statement_runs')->cascadeOnDelete();
            $table->string('source_type', 120);
            $table->unsignedBigInteger('source_id');
            $table->date('transaction_date');
            $table->string('description');
            $table->decimal('debit_amount', 18, 2)->default(0);
            $table->decimal('credit_amount', 18, 2)->default(0);
            $table->decimal('balance_after', 18, 2)->default(0);
            $table->longText('metadata_snapshot')->nullable();
            $table->timestamps();
        });

        Schema::create('receivable_writeoff_recommendations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('receivable_case_id')->constrained('receivable_cases')->cascadeOnDelete();
            $table->string('source_type', 120);
            $table->unsignedBigInteger('source_id');
            $table->decimal('recommended_amount', 18, 2);
            $table->text('reason');
            $table->string('status', 30)->default('recommended');
            $table->foreignId('recommended_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('recommended_at')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('approved_at')->nullable();
            $table->foreignId('rejected_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('rejected_at')->nullable();
            $table->string('rejection_reason')->nullable();
            $table->unsignedBigInteger('linked_writeoff_id')->nullable();
            $table->unsignedBigInteger('linked_credit_note_id')->nullable();
            $table->longText('metadata_snapshot')->nullable();
            $table->timestamps();
        });

        Schema::create('receivable_creditnote_recommendations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('receivable_case_id')->constrained('receivable_cases')->cascadeOnDelete();
            $table->string('source_type', 120);
            $table->unsignedBigInteger('source_id');
            $table->decimal('recommended_amount', 18, 2);
            $table->text('reason');
            $table->string('status', 30)->default('recommended');
            $table->foreignId('recommended_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('recommended_at')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('approved_at')->nullable();
            $table->foreignId('rejected_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('rejected_at')->nullable();
            $table->string('rejection_reason')->nullable();
            $table->unsignedBigInteger('linked_writeoff_id')->nullable();
            $table->unsignedBigInteger('linked_credit_note_id')->nullable();
            $table->longText('metadata_snapshot')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('receivable_creditnote_recommendations');
        Schema::dropIfExists('receivable_writeoff_recommendations');
        Schema::dropIfExists('receivable_statement_items');
        Schema::dropIfExists('receivable_statement_runs');
        Schema::dropIfExists('receivable_dunning_notices');
        Schema::dropIfExists('receivable_assignments');
        Schema::dropIfExists('receivable_disputes');
        Schema::dropIfExists('receivable_promises');
        Schema::dropIfExists('receivable_followups');
        Schema::dropIfExists('receivable_case_items');
        Schema::dropIfExists('receivable_cases');
    }
};
