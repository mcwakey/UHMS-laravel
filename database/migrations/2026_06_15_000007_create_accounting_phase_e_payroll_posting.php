<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payroll_runs', function (Blueprint $table) {
            $table->string('accounting_status')->default('pending')->after('status');
            $table->foreignId('journal_entry_id')->nullable()->after('accounting_status')->constrained('journal_entries')->nullOnDelete();
            $table->dateTime('accounting_posted_at')->nullable()->after('journal_entry_id');
            $table->text('accounting_error')->nullable()->after('accounting_posted_at');
            $table->foreignId('reversal_journal_entry_id')->nullable()->after('accounting_error')->constrained('journal_entries')->nullOnDelete();
            $table->dateTime('reversed_at')->nullable()->after('reversal_journal_entry_id');
            $table->foreignId('reversed_by')->nullable()->after('reversed_at')->constrained('users')->nullOnDelete();
            $table->text('reversal_reason')->nullable()->after('reversed_by');
            $table->string('settlement_status')->default('open')->after('reversal_reason');
            $table->decimal('settled_amount', 18, 2)->default(0)->after('settlement_status');
            $table->index(['accounting_status', 'settlement_status'], 'payroll_run_accounting_status_idx');
        });

        Schema::create('payroll_settlements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payroll_run_id')->constrained()->cascadeOnDelete();
            $table->string('settlement_number')->unique();
            $table->date('settlement_date');
            $table->decimal('amount', 18, 2);
            $table->foreignId('payment_account_id')->constrained('accounts')->restrictOnDelete();
            $table->string('status')->default('posted');
            $table->string('accounting_status')->default('pending');
            $table->foreignId('journal_entry_id')->nullable()->constrained('journal_entries')->nullOnDelete();
            $table->foreignId('reversal_journal_entry_id')->nullable()->constrained('journal_entries')->nullOnDelete();
            $table->dateTime('posted_at')->nullable();
            $table->foreignId('posted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('reversed_at')->nullable();
            $table->foreignId('reversed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('reversal_reason')->nullable();
            $table->text('accounting_error')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['payroll_run_id', 'status'], 'payroll_settlement_run_status_idx');
            $table->index(['accounting_status', 'settlement_date'], 'payroll_settlement_accounting_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payroll_settlements');

        Schema::table('payroll_runs', function (Blueprint $table) {
            $table->dropIndex('payroll_run_accounting_status_idx');
            $table->dropConstrainedForeignId('journal_entry_id');
            $table->dropConstrainedForeignId('reversal_journal_entry_id');
            $table->dropConstrainedForeignId('reversed_by');
            $table->dropColumn([
                'accounting_status',
                'accounting_posted_at',
                'accounting_error',
                'reversed_at',
                'reversal_reason',
                'settlement_status',
                'settled_amount',
            ]);
        });
    }
};
