<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('accounting_reconciliation_runs', function (Blueprint $table) {
            $table->id();
            $table->string('reconciliation_type', 40);
            $table->date('period_start');
            $table->date('period_end');
            $table->date('as_of_date');
            $table->string('status', 20)->default('draft');
            $table->decimal('tolerance_amount', 18, 2)->default(0.01);
            $table->decimal('subledger_total', 18, 2)->default(0);
            $table->decimal('gl_total', 18, 2)->default(0);
            $table->decimal('difference_amount', 18, 2)->default(0);
            $table->string('difference_classification', 40)->nullable();
            $table->longText('source_snapshot')->nullable();
            $table->longText('gl_snapshot')->nullable();
            $table->longText('summary_snapshot')->nullable();
            $table->foreignId('started_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('started_at')->nullable();
            $table->foreignId('completed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('completed_at')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('approved_at')->nullable();
            $table->foreignId('cancelled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('cancelled_at')->nullable();
            $table->text('cancellation_reason')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['reconciliation_type', 'status'], 'recon_run_type_status_idx');
            $table->index(['period_start', 'period_end'], 'recon_run_period_idx');
            $table->index(['as_of_date', 'status'], 'recon_run_asof_status_idx');
        });

        Schema::create('accounting_reconciliation_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('accounting_reconciliation_run_id');
            $table->string('source_type', 120);
            $table->unsignedBigInteger('source_id')->nullable();
            $table->string('source_reference')->nullable();
            $table->text('source_description')->nullable();
            $table->unsignedBigInteger('gl_account_id')->nullable();
            $table->decimal('subledger_amount', 18, 2)->default(0);
            $table->decimal('gl_amount', 18, 2)->default(0);
            $table->decimal('difference_amount', 18, 2)->default(0);
            $table->string('classification', 40);
            $table->string('resolution_status', 30)->default('open');
            $table->longText('metadata_snapshot')->nullable();
            $table->timestamps();

            $table->index(['accounting_reconciliation_run_id', 'classification'], 'recon_item_run_class_idx');
            $table->index(['accounting_reconciliation_run_id', 'resolution_status'], 'recon_item_run_res_idx');
            $table->index(['source_type', 'source_id'], 'recon_item_source_idx');
            $table->foreign('accounting_reconciliation_run_id', 'recon_item_run_fk')
                ->references('id')->on('accounting_reconciliation_runs')->cascadeOnDelete();
            $table->foreign('gl_account_id', 'recon_item_account_fk')
                ->references('id')->on('accounts')->nullOnDelete();
        });

        Schema::create('accounting_reconciliation_resolutions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('accounting_reconciliation_run_id');
            $table->unsignedBigInteger('accounting_reconciliation_item_id')->nullable();
            $table->string('resolution_type', 50);
            $table->text('resolution_note');
            $table->unsignedBigInteger('linked_journal_entry_id')->nullable();
            $table->unsignedBigInteger('linked_posting_attempt_id')->nullable();
            $table->string('linked_source_type', 120)->nullable();
            $table->unsignedBigInteger('linked_source_id')->nullable();
            $table->foreignId('resolved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('resolved_at');
            $table->longText('metadata_snapshot')->nullable();
            $table->timestamps();

            $table->index(['accounting_reconciliation_run_id', 'resolution_type'], 'recon_res_run_type_idx');
            $table->index(['accounting_reconciliation_item_id', 'resolved_at'], 'recon_res_item_date_idx');
            $table->foreign('accounting_reconciliation_run_id', 'recon_res_run_fk')
                ->references('id')->on('accounting_reconciliation_runs')->cascadeOnDelete();
            $table->foreign('accounting_reconciliation_item_id', 'recon_res_item_fk')
                ->references('id')->on('accounting_reconciliation_items')->cascadeOnDelete();
            $table->foreign('linked_journal_entry_id', 'recon_res_journal_fk')
                ->references('id')->on('journal_entries')->nullOnDelete();
            $table->foreign('linked_posting_attempt_id', 'recon_res_attempt_fk')
                ->references('id')->on('accounting_posting_attempts')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('accounting_reconciliation_resolutions');
        Schema::dropIfExists('accounting_reconciliation_items');
        Schema::dropIfExists('accounting_reconciliation_runs');
    }
};
