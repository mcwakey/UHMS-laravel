<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('accounting_posting_attempts', function (Blueprint $table) {
            $table->id();
            $table->string('source_module', 50);
            $table->string('source_type', 100);
            $table->unsignedBigInteger('source_id');
            $table->string('posting_type', 50);
            $table->unsignedInteger('posting_version')->default(1);
            $table->string('idempotency_key', 191)->unique('acct_post_attempt_idem_uq');
            $table->string('status', 24)->default('pending');
            $table->foreignId('journal_entry_id')->nullable()->constrained('journal_entries')->nullOnDelete();
            $table->foreignId('reversal_journal_entry_id')->nullable()->constrained('journal_entries')->nullOnDelete();
            $table->unsignedInteger('attempt_count')->default(0);
            $table->timestamp('first_attempted_at')->nullable();
            $table->timestamp('last_attempted_at')->nullable();
            $table->timestamp('next_retry_at')->nullable();
            $table->string('error_code', 120)->nullable();
            $table->text('error_message')->nullable();
            $table->longText('error_context')->nullable();
            $table->longText('source_snapshot')->nullable();
            $table->longText('posting_snapshot')->nullable();
            $table->foreignId('resolved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('resolved_at')->nullable();
            $table->string('resolution_type', 80)->nullable();
            $table->text('resolution_note')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['source_module', 'source_type', 'source_id'], 'acct_post_attempt_source_idx');
            $table->index(['source_type', 'source_id', 'posting_type', 'posting_version'], 'acct_post_attempt_identity_idx');
            $table->index(['status', 'last_attempted_at'], 'acct_post_attempt_status_idx');
            $table->index('journal_entry_id', 'acct_post_attempt_journal_idx');
            $table->index('reversal_journal_entry_id', 'acct_post_attempt_reversal_idx');
        });

        Schema::create('accounting_posting_attempt_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('accounting_posting_attempt_id')
                ->constrained('accounting_posting_attempts', indexName: 'acct_post_event_attempt_fk')
                ->restrictOnDelete();
            $table->string('event_type', 80);
            $table->string('from_status', 24)->nullable();
            $table->string('to_status', 24)->nullable();
            $table->string('error_code', 120)->nullable();
            $table->text('error_message')->nullable();
            $table->longText('context')->nullable();
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('occurred_at');
            $table->timestamps();

            $table->index(['accounting_posting_attempt_id', 'occurred_at'], 'acct_post_event_timeline_idx');
        });

        Schema::create('accounting_account_mappings', function (Blueprint $table) {
            $table->id();
            $table->string('mapping_scope', 50);
            $table->string('mapping_key', 60);
            $table->string('mapping_value', 80);
            $table->foreignId('account_id')->constrained('accounts')->restrictOnDelete();
            $table->unsignedBigInteger('facility_id')->nullable();
            $table->foreignId('department_id')->nullable()->constrained('departments')->nullOnDelete();
            $table->unsignedBigInteger('branch_id')->nullable();
            $table->string('currency', 3)->nullable();
            $table->date('effective_from');
            $table->date('effective_to')->nullable();
            $table->integer('priority')->default(0);
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['mapping_scope', 'mapping_key', 'mapping_value'], 'acct_map_lookup_idx');
            $table->index(['is_active', 'effective_from', 'effective_to'], 'acct_map_effective_idx');
            $table->index(['facility_id', 'department_id', 'branch_id'], 'acct_map_dimension_idx');
            $table->index(['currency', 'priority'], 'acct_map_currency_priority_idx');
        });

        Schema::table('journal_entries', function (Blueprint $table) {
            $table->string('idempotency_key', 191)->nullable()->after('source_module');
            $table->unique('idempotency_key', 'journal_entries_idem_uq');
        });
    }

    public function down(): void
    {
        Schema::table('journal_entries', function (Blueprint $table) {
            $table->dropUnique('journal_entries_idem_uq');
            $table->dropColumn('idempotency_key');
        });

        Schema::dropIfExists('accounting_account_mappings');
        Schema::dropIfExists('accounting_posting_attempt_events');
        Schema::dropIfExists('accounting_posting_attempts');
    }
};
