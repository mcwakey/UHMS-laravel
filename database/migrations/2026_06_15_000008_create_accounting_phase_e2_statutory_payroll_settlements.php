<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payroll_statutory_settlements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payroll_run_id')->constrained()->cascadeOnDelete();
            $table->string('settlement_number')->unique();
            $table->string('liability_type', 20);
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
            $table->index(['payroll_run_id', 'liability_type', 'status'], 'payroll_stat_settle_run_type_status_idx');
            $table->index(['accounting_status', 'settlement_date'], 'payroll_stat_settle_accounting_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payroll_statutory_settlements');
    }
};
