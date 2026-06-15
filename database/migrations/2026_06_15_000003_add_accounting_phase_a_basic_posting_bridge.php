<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('financial_entries', function (Blueprint $table) {
            $table->string('approval_status', 24)->nullable()->after('approved_by');
            $table->string('accounting_status', 24)->nullable()->after('approval_status');
            $table->foreignId('journal_entry_id')->nullable()->after('accounting_status')->constrained('journal_entries')->nullOnDelete();
            $table->timestamp('accounting_posted_at')->nullable()->after('journal_entry_id');
            $table->text('accounting_error')->nullable()->after('accounting_posted_at');
            $table->foreignId('reversal_journal_entry_id')->nullable()->after('accounting_error')->constrained('journal_entries')->nullOnDelete();
            $table->timestamp('reversed_at')->nullable()->after('reversal_journal_entry_id');
            $table->foreignId('reversed_by')->nullable()->after('reversed_at')->constrained('users')->nullOnDelete();
            $table->text('reversal_reason')->nullable()->after('reversed_by');
            $table->unsignedInteger('posting_version')->nullable()->after('reversal_reason');
            $table->foreignId('posted_by')->nullable()->after('posting_version')->constrained('users')->nullOnDelete();

            $table->index(['approval_status', 'accounting_status'], 'fin_entries_approval_accounting_idx');
            $table->index('journal_entry_id', 'fin_entries_journal_idx');
            $table->index('reversal_journal_entry_id', 'fin_entries_reversal_idx');
        });

        DB::table('financial_entries')->whereNotNull('approved_by')->update([
            'approval_status' => 'approved',
            'accounting_status' => 'eligible',
            'posting_version' => 1,
        ]);
        DB::table('financial_entries')->whereNull('approved_by')->update([
            'approval_status' => 'pending',
            'accounting_status' => 'pending',
            'posting_version' => 1,
        ]);

        Schema::create('accounting_posting_templates', function (Blueprint $table) {
            $table->id();
            $table->string('code', 80)->unique();
            $table->string('name', 160);
            $table->string('source_module', 50);
            $table->string('entry_type', 24);
            $table->string('posting_type', 50);
            $table->date('effective_from');
            $table->date('effective_to')->nullable();
            $table->string('status', 24)->default('draft');
            $table->text('description')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['source_module', 'entry_type', 'posting_type', 'status'], 'acct_template_lookup_idx');
            $table->index(['effective_from', 'effective_to'], 'acct_template_effective_idx');
        });

        Schema::create('accounting_posting_template_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('template_id')->constrained('accounting_posting_templates')->cascadeOnDelete();
            $table->unsignedInteger('line_order');
            $table->string('side', 8);
            $table->string('account_source_type', 40);
            $table->foreignId('fixed_account_id')->nullable()->constrained('accounts')->restrictOnDelete();
            $table->string('mapping_scope', 50)->nullable();
            $table->string('mapping_key_source', 60)->nullable();
            $table->string('mapping_value_source', 80)->nullable();
            $table->string('amount_source', 40)->default('entry_amount');
            $table->string('description_template', 255)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['template_id', 'line_order'], 'acct_template_line_order_uq');
            $table->index(['template_id', 'is_active'], 'acct_template_line_active_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('accounting_posting_template_lines');
        Schema::dropIfExists('accounting_posting_templates');

        Schema::table('financial_entries', function (Blueprint $table) {
            $table->dropForeign(['journal_entry_id']);
            $table->dropForeign(['reversal_journal_entry_id']);
            $table->dropForeign(['reversed_by']);
            $table->dropForeign(['posted_by']);
            $table->dropIndex('fin_entries_approval_accounting_idx');
            $table->dropIndex('fin_entries_journal_idx');
            $table->dropIndex('fin_entries_reversal_idx');
            $table->dropColumn([
                'approval_status',
                'accounting_status',
                'journal_entry_id',
                'accounting_posted_at',
                'accounting_error',
                'reversal_journal_entry_id',
                'reversed_at',
                'reversed_by',
                'reversal_reason',
                'posting_version',
                'posted_by',
            ]);
        });
    }
};
