<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tax_types', function (Blueprint $table) {
            $table->id();
            $table->string('code', 40)->unique();
            $table->string('name');
            $table->string('category', 30)->default('liability');
            $table->string('authority_name')->nullable();
            $table->string('return_frequency', 20)->default('monthly');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('tax_registrations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tax_type_id')->constrained('tax_types')->cascadeOnDelete();
            $table->string('registration_number');
            $table->string('authority_name')->nullable();
            $table->date('effective_from');
            $table->date('effective_to')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('tax_account_mappings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tax_type_id')->constrained('tax_types')->cascadeOnDelete();
            $table->foreignId('payable_account_id')->nullable()->constrained('accounts')->nullOnDelete();
            $table->foreignId('receivable_account_id')->nullable()->constrained('accounts')->nullOnDelete();
            $table->date('effective_from');
            $table->date('effective_to')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('tax_ledger_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tax_type_id')->constrained('tax_types')->restrictOnDelete();
            $table->foreignId('fiscal_year_id')->nullable()->constrained('fiscal_years')->nullOnDelete();
            $table->foreignId('accounting_period_id')->nullable()->constrained('accounting_periods')->nullOnDelete();
            $table->date('entry_date');
            $table->string('source_type', 120);
            $table->unsignedBigInteger('source_id')->nullable();
            $table->string('source_reference')->nullable();
            $table->string('direction', 24);
            $table->decimal('tax_base_amount', 18, 2)->default(0);
            $table->decimal('tax_amount', 18, 2);
            $table->decimal('remaining_amount', 18, 2);
            $table->string('status', 24)->default('open');
            $table->foreignId('journal_entry_id')->nullable()->constrained('journal_entries')->nullOnDelete();
            $table->longText('metadata_snapshot')->nullable();
            $table->timestamps();

            $table->unique(['tax_type_id', 'source_type', 'source_id', 'direction'], 'tax_ledger_source_unique');
            $table->index(['tax_type_id', 'entry_date', 'status'], 'tax_ledger_type_date_status_idx');
            $table->index(['fiscal_year_id', 'accounting_period_id'], 'tax_ledger_period_idx');
        });

        Schema::create('tax_return_periods', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tax_type_id')->constrained('tax_types')->cascadeOnDelete();
            $table->date('period_start');
            $table->date('period_end');
            $table->string('status', 24)->default('open');
            $table->timestamps();

            $table->unique(['tax_type_id', 'period_start', 'period_end'], 'tax_return_period_unique');
        });

        Schema::create('tax_returns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tax_return_period_id')->constrained('tax_return_periods')->cascadeOnDelete();
            $table->string('return_number')->unique();
            $table->string('status', 24)->default('prepared');
            $table->decimal('total_tax_due', 18, 2)->default(0);
            $table->decimal('total_payments', 18, 2)->default(0);
            $table->decimal('balance_due', 18, 2)->default(0);
            $table->foreignId('prepared_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('prepared_at')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('approved_at')->nullable();
            $table->timestamps();
        });

        Schema::create('tax_return_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tax_return_id')->constrained('tax_returns')->cascadeOnDelete();
            $table->foreignId('tax_ledger_entry_id')->nullable()->constrained('tax_ledger_entries')->nullOnDelete();
            $table->string('line_type', 40);
            $table->decimal('tax_base_amount', 18, 2)->default(0);
            $table->decimal('tax_amount', 18, 2);
            $table->timestamps();
        });

        Schema::create('tax_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tax_type_id')->constrained('tax_types')->restrictOnDelete();
            $table->string('payment_number')->unique();
            $table->date('payment_date');
            $table->decimal('amount', 18, 2);
            $table->decimal('unallocated_amount', 18, 2);
            $table->foreignId('payment_account_id')->constrained('accounts')->restrictOnDelete();
            $table->string('status', 24)->default('posted');
            $table->foreignId('journal_entry_id')->nullable()->constrained('journal_entries')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('tax_payment_allocations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tax_payment_id')->constrained('tax_payments')->cascadeOnDelete();
            $table->foreignId('tax_return_id')->nullable()->constrained('tax_returns')->cascadeOnDelete();
            $table->foreignId('tax_ledger_entry_id')->nullable()->constrained('tax_ledger_entries')->cascadeOnDelete();
            $table->decimal('amount', 18, 2);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('withholding_certificates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tax_ledger_entry_id')->nullable()->constrained('tax_ledger_entries')->nullOnDelete();
            $table->string('certificate_number')->unique();
            $table->date('certificate_date');
            $table->string('counterparty_name')->nullable();
            $table->decimal('withheld_amount', 18, 2);
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('withholding_certificates');
        Schema::dropIfExists('tax_payment_allocations');
        Schema::dropIfExists('tax_payments');
        Schema::dropIfExists('tax_return_lines');
        Schema::dropIfExists('tax_returns');
        Schema::dropIfExists('tax_return_periods');
        Schema::dropIfExists('tax_ledger_entries');
        Schema::dropIfExists('tax_account_mappings');
        Schema::dropIfExists('tax_registrations');
        Schema::dropIfExists('tax_types');
    }
};
