<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->foreignId('reversal_journal_entry_id')->nullable()->after('journal_entry_id')->constrained('journal_entries')->nullOnDelete();
        });

        Schema::table('invoice_discounts', function (Blueprint $table) {
            $table->foreignId('reverses_discount_id')->nullable()->after('journal_entry_id')->constrained('invoice_discounts')->nullOnDelete();
            $table->foreignId('reversal_journal_entry_id')->nullable()->after('reverses_discount_id')->constrained('journal_entries')->nullOnDelete();
            $table->timestamp('reversed_at')->nullable()->after('accounting_posted_at');
            $table->foreignId('reversed_by')->nullable()->after('reversed_at')->constrained('users')->nullOnDelete();
            $table->text('reversal_reason')->nullable()->after('reversed_by');
        });

        Schema::table('credit_notes', function (Blueprint $table) {
            $table->foreignId('reversal_journal_entry_id')->nullable()->after('journal_entry_id')->constrained('journal_entries')->nullOnDelete();
            $table->timestamp('reversed_at')->nullable()->after('accounting_posted_at');
            $table->foreignId('reversed_by')->nullable()->after('reversed_at')->constrained('users')->nullOnDelete();
            $table->text('reversal_reason')->nullable()->after('reversed_by');
        });
    }

    public function down(): void
    {
        Schema::table('credit_notes', function (Blueprint $table) {
            $table->dropForeign(['reversal_journal_entry_id']);
            $table->dropForeign(['reversed_by']);
            $table->dropColumn([
                'reversal_journal_entry_id',
                'reversed_at',
                'reversed_by',
                'reversal_reason',
            ]);
        });

        Schema::table('invoice_discounts', function (Blueprint $table) {
            $table->dropForeign(['reverses_discount_id']);
            $table->dropForeign(['reversal_journal_entry_id']);
            $table->dropForeign(['reversed_by']);
            $table->dropColumn([
                'reverses_discount_id',
                'reversal_journal_entry_id',
                'reversed_at',
                'reversed_by',
                'reversal_reason',
            ]);
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->dropForeign(['reversal_journal_entry_id']);
            $table->dropColumn('reversal_journal_entry_id');
        });
    }
};
