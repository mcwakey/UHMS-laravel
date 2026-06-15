<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('accounting_posting_attempts', function (Blueprint $table) {
            $table->foreignId('resolution_journal_entry_id')->nullable()->after('resolution_note')->constrained('journal_entries')->nullOnDelete();
            $table->string('resolution_source_type', 120)->nullable()->after('resolution_journal_entry_id');
            $table->unsignedBigInteger('resolution_source_id')->nullable()->after('resolution_source_type');
            $table->string('resolution_reference', 191)->nullable()->after('resolution_source_id');
            $table->text('resolution_evidence')->nullable()->after('resolution_reference');
            $table->text('materiality_note')->nullable()->after('resolution_evidence');
            $table->date('waiver_review_date')->nullable()->after('materiality_note');

            $table->index('resolution_journal_entry_id', 'acct_post_resolution_journal_idx');
            $table->index(['resolution_source_type', 'resolution_source_id'], 'acct_post_resolution_source_idx');
            $table->index(['status', 'resolved_at'], 'acct_post_resolution_status_idx');
        });
    }

    public function down(): void
    {
        Schema::table('accounting_posting_attempts', function (Blueprint $table) {
            $table->dropForeign(['resolution_journal_entry_id']);
            $table->dropIndex('acct_post_resolution_journal_idx');
            $table->dropIndex('acct_post_resolution_source_idx');
            $table->dropIndex('acct_post_resolution_status_idx');
            $table->dropColumn([
                'resolution_journal_entry_id',
                'resolution_source_type',
                'resolution_source_id',
                'resolution_reference',
                'resolution_evidence',
                'materiality_note',
                'waiver_review_date',
            ]);
        });
    }
};
