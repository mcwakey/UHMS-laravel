<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Accounting Execution Phase B — Bank Accounts, Statement Import &
 * Formal Bank Reconciliation.
 *
 * Safe additive migration. String statuses (validated in the application),
 * DECIMAL(18,2) money, LONGTEXT JSON snapshots (MariaDB 10.1 safe), explicit
 * short index names, and no cascade-deletes on financial history.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('bank_accounts')) {
            Schema::create('bank_accounts', function (Blueprint $table) {
                $table->id();
                $table->string('name', 160);
                $table->string('bank_name', 160);
                $table->string('branch_name', 160)->nullable();
                $table->string('account_name', 160)->nullable();
                $table->string('account_number_masked', 64)->nullable();
                $table->string('account_number_hash', 64)->nullable();
                $table->string('currency', 3)->default('GHS');
                $table->foreignId('gl_account_id')->constrained('accounts')->restrictOnDelete();
                $table->date('opening_date')->nullable();
                $table->decimal('opening_balance', 18, 2)->default(0);
                $table->boolean('is_active')->default(true);
                $table->text('notes')->nullable();
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();

                $table->index(['is_active'], 'bank_acct_active_idx');
                $table->index(['gl_account_id'], 'bank_acct_gl_idx');
                $table->index(['account_number_hash'], 'bank_acct_hash_idx');
            });
        }

        if (! Schema::hasTable('bank_statement_imports')) {
            Schema::create('bank_statement_imports', function (Blueprint $table) {
                $table->id();
                $table->foreignId('bank_account_id')->constrained('bank_accounts')->restrictOnDelete();
                $table->string('format', 24)->default('csv');
                $table->string('original_filename', 255)->nullable();
                $table->string('file_hash', 64)->nullable();
                $table->date('period_start')->nullable();
                $table->date('period_end')->nullable();
                $table->decimal('opening_balance', 18, 2)->nullable();
                $table->decimal('closing_balance', 18, 2)->nullable();
                $table->decimal('total_debit', 18, 2)->default(0);
                $table->decimal('total_credit', 18, 2)->default(0);
                $table->unsignedInteger('line_count')->default(0);
                $table->string('status', 24)->default('draft');
                $table->foreignId('imported_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('imported_at')->nullable();
                $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('approved_at')->nullable();
                $table->foreignId('rejected_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('rejected_at')->nullable();
                $table->text('rejection_reason')->nullable();
                $table->longText('error_summary')->nullable();
                $table->longText('metadata_snapshot')->nullable();
                $table->timestamps();

                $table->unique(['bank_account_id', 'file_hash'], 'bank_imp_file_uq');
                $table->index(['bank_account_id', 'status'], 'bank_imp_acct_status_idx');
            });
        }

        if (! Schema::hasTable('bank_statement_lines')) {
            Schema::create('bank_statement_lines', function (Blueprint $table) {
                $table->id();
                $table->foreignId('bank_statement_import_id')->constrained('bank_statement_imports')->restrictOnDelete();
                $table->foreignId('bank_account_id')->constrained('bank_accounts')->restrictOnDelete();
                $table->unsignedInteger('line_number');
                $table->date('transaction_date')->nullable();
                $table->date('value_date')->nullable();
                $table->string('reference', 191)->nullable();
                $table->string('normalized_reference', 191)->nullable();
                $table->string('description', 255)->nullable();
                $table->decimal('debit_amount', 18, 2)->default(0);
                $table->decimal('credit_amount', 18, 2)->default(0);
                $table->decimal('balance_after', 18, 2)->nullable();
                $table->string('external_transaction_id', 191)->nullable();
                $table->string('line_hash', 64)->nullable();
                $table->string('match_status', 24)->default('unmatched');
                $table->decimal('matched_amount', 18, 2)->default(0);
                $table->decimal('unmatched_amount', 18, 2)->default(0);
                $table->longText('metadata_snapshot')->nullable();
                $table->timestamps();

                $table->index(['bank_statement_import_id'], 'bank_line_import_idx');
                $table->index(['bank_account_id', 'match_status'], 'bank_line_acct_match_idx');
                $table->index(['line_hash'], 'bank_line_hash_idx');
            });
        }

        if (! Schema::hasTable('bank_reconciliations')) {
            Schema::create('bank_reconciliations', function (Blueprint $table) {
                $table->id();
                $table->foreignId('bank_account_id')->constrained('bank_accounts')->restrictOnDelete();
                $table->date('period_start');
                $table->date('period_end');
                $table->decimal('statement_opening_balance', 18, 2)->default(0);
                $table->decimal('statement_closing_balance', 18, 2)->default(0);
                $table->decimal('book_opening_balance', 18, 2)->default(0);
                $table->decimal('book_closing_balance', 18, 2)->default(0);
                $table->decimal('outstanding_deposits_total', 18, 2)->default(0);
                $table->decimal('outstanding_withdrawals_total', 18, 2)->default(0);
                $table->decimal('adjustments_total', 18, 2)->default(0);
                $table->decimal('difference', 18, 2)->default(0);
                $table->string('status', 24)->default('draft');
                $table->foreignId('prepared_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('prepared_at')->nullable();
                $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('approved_at')->nullable();
                $table->foreignId('reopened_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('reopened_at')->nullable();
                $table->text('reopen_reason')->nullable();
                $table->foreignId('reversed_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('reversed_at')->nullable();
                $table->text('reversal_reason')->nullable();
                $table->text('notes')->nullable();
                $table->timestamps();

                $table->index(['bank_account_id', 'status'], 'bank_recon_acct_status_idx');
                $table->index(['period_start', 'period_end'], 'bank_recon_period_idx');
            });
        }

        if (! Schema::hasTable('bank_reconciliation_matches')) {
            Schema::create('bank_reconciliation_matches', function (Blueprint $table) {
                $table->id();
                $table->foreignId('bank_reconciliation_id')->constrained('bank_reconciliations')->restrictOnDelete();
                $table->foreignId('bank_statement_line_id')->constrained('bank_statement_lines')->restrictOnDelete();
                $table->string('matchable_type', 191)->nullable();
                $table->unsignedBigInteger('matchable_id')->nullable();
                $table->decimal('matched_amount', 18, 2)->default(0);
                $table->string('match_method', 32)->default('manual');
                $table->decimal('confidence_score', 5, 2)->nullable();
                $table->string('status', 24)->default('active');
                $table->foreignId('matched_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('matched_at')->nullable();
                $table->foreignId('unmatched_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('unmatched_at')->nullable();
                $table->text('unmatch_reason')->nullable();
                $table->longText('metadata_snapshot')->nullable();
                $table->timestamps();

                $table->index(['bank_reconciliation_id', 'status'], 'bank_match_recon_idx');
                $table->index(['bank_statement_line_id'], 'bank_match_line_idx');
                $table->index(['matchable_type', 'matchable_id'], 'bank_match_matchable_idx');
            });
        }

        if (! Schema::hasTable('bank_reconciliation_adjustments')) {
            Schema::create('bank_reconciliation_adjustments', function (Blueprint $table) {
                $table->id();
                $table->foreignId('bank_reconciliation_id')->constrained('bank_reconciliations')->restrictOnDelete();
                $table->foreignId('bank_account_id')->constrained('bank_accounts')->restrictOnDelete();
                $table->string('type', 32);
                $table->string('description', 255)->nullable();
                $table->decimal('amount', 18, 2);
                $table->string('side', 8);
                $table->foreignId('account_id')->nullable()->constrained('accounts')->nullOnDelete();
                $table->foreignId('journal_entry_id')->nullable()->constrained('journal_entries')->nullOnDelete();
                $table->string('status', 24)->default('draft');
                $table->foreignId('proposed_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('proposed_at')->nullable();
                $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('approved_at')->nullable();
                $table->foreignId('posted_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('posted_at')->nullable();
                $table->foreignId('rejected_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('rejected_at')->nullable();
                $table->text('rejection_reason')->nullable();
                $table->longText('metadata_snapshot')->nullable();
                $table->timestamps();

                $table->index(['bank_reconciliation_id', 'status'], 'bank_adj_recon_status_idx');
                $table->index(['journal_entry_id'], 'bank_adj_journal_idx');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('bank_reconciliation_adjustments');
        Schema::dropIfExists('bank_reconciliation_matches');
        Schema::dropIfExists('bank_reconciliations');
        Schema::dropIfExists('bank_statement_lines');
        Schema::dropIfExists('bank_statement_imports');
        Schema::dropIfExists('bank_accounts');
    }
};
