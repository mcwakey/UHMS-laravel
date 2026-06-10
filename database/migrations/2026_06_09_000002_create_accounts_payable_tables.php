<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Accounting Phase 5 — Accounts Payable.
 *
 * supplier_payables : per-liability AP record (from goods receipt / supplier
 *                     invoice) used for AP aging and balance reconciliation.
 * supplier_payments : money paid to a supplier, with accounting status.
 * Accounting columns are added to goods_received_notes and purchase_returns so
 * each can post once (idempotent) and surface its journal entry.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('supplier_payables', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('supplier_id');
            $table->unsignedBigInteger('purchase_order_id')->nullable();
            $table->unsignedBigInteger('goods_received_note_id')->nullable();
            $table->unsignedBigInteger('supplier_invoice_id')->nullable();
            $table->unsignedBigInteger('supplier_ledger_entry_id')->nullable();

            $table->decimal('original_amount', 14, 2)->default(0);
            $table->decimal('paid_amount', 14, 2)->default(0);
            $table->decimal('credit_note_amount', 14, 2)->default(0);
            $table->decimal('return_amount', 14, 2)->default(0);
            $table->decimal('adjustment_amount', 14, 2)->default(0);
            $table->decimal('balance', 14, 2)->default(0);

            $table->date('invoice_date')->nullable();
            $table->date('aging_start_date');
            $table->date('due_date')->nullable();
            $table->string('status', 20)->default('pending')->index();

            $table->unsignedBigInteger('journal_entry_id')->nullable();
            $table->string('accounting_status', 20)->nullable();
            $table->timestamp('accounting_posted_at')->nullable();
            $table->text('accounting_error')->nullable();

            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();

            $table->index(['supplier_id', 'status']);
            $table->foreign('supplier_id')->references('id')->on('suppliers')->cascadeOnDelete();
            $table->foreign('purchase_order_id')->references('id')->on('purchase_orders')->nullOnDelete();
            $table->foreign('goods_received_note_id')->references('id')->on('goods_received_notes')->nullOnDelete();
        });

        Schema::create('supplier_payments', function (Blueprint $table) {
            $table->id();
            $table->string('payment_number')->nullable()->unique();
            $table->unsignedBigInteger('supplier_id');
            $table->unsignedBigInteger('supplier_payable_id')->nullable();
            $table->unsignedBigInteger('supplier_ledger_entry_id')->nullable();
            $table->date('payment_date');
            $table->decimal('amount', 14, 2);
            $table->string('payment_method', 30)->default('cash'); // cash | bank | mobile_money
            $table->string('reference')->nullable();
            $table->text('notes')->nullable();

            $table->unsignedBigInteger('journal_entry_id')->nullable();
            $table->string('accounting_status', 20)->nullable();
            $table->timestamp('accounting_posted_at')->nullable();
            $table->text('accounting_error')->nullable();

            $table->unsignedBigInteger('reversal_journal_entry_id')->nullable();
            $table->timestamp('reversed_at')->nullable();
            $table->unsignedBigInteger('reversed_by')->nullable();
            $table->string('reversal_reason')->nullable();

            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();

            $table->index(['supplier_id']);
            $table->foreign('supplier_id')->references('id')->on('suppliers')->cascadeOnDelete();
            $table->foreign('supplier_payable_id')->references('id')->on('supplier_payables')->nullOnDelete();
        });

        Schema::table('goods_received_notes', function (Blueprint $table) {
            $table->unsignedBigInteger('supplier_payable_id')->nullable()->after('notes');
            $table->unsignedBigInteger('journal_entry_id')->nullable()->after('supplier_payable_id');
            $table->string('accounting_status', 20)->nullable()->after('journal_entry_id');
            $table->timestamp('accounting_posted_at')->nullable()->after('accounting_status');
            $table->text('accounting_error')->nullable()->after('accounting_posted_at');
        });

        Schema::table('purchase_returns', function (Blueprint $table) {
            $table->unsignedBigInteger('journal_entry_id')->nullable()->after('posted_at');
            $table->string('accounting_status', 20)->nullable()->after('journal_entry_id');
            $table->timestamp('accounting_posted_at')->nullable()->after('accounting_status');
            $table->text('accounting_error')->nullable()->after('accounting_posted_at');
        });
    }

    public function down(): void
    {
        Schema::table('purchase_returns', function (Blueprint $table) {
            $table->dropColumn(['journal_entry_id', 'accounting_status', 'accounting_posted_at', 'accounting_error']);
        });
        Schema::table('goods_received_notes', function (Blueprint $table) {
            $table->dropColumn(['supplier_payable_id', 'journal_entry_id', 'accounting_status', 'accounting_posted_at', 'accounting_error']);
        });
        Schema::dropIfExists('supplier_payments');
        Schema::dropIfExists('supplier_payables');
    }
};
