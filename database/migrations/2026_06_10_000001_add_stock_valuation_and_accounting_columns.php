<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Accounting Phase 6 — Stock valuation + inventory accounting.
 *
 * stock_balances : weighted-average cost + total value of on-hand stock.
 * stock_movements: per-movement cost (for OUT, the costed value used to post),
 *                  valuation method, and accounting status so each costed
 *                  movement posts once (idempotent).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stock_balances', function (Blueprint $table) {
            $table->decimal('average_cost', 14, 4)->default(0)->after('quantity_on_hand');
            $table->decimal('total_value', 16, 2)->default(0)->after('average_cost');
            $table->timestamp('last_valued_at')->nullable()->after('total_value');
        });

        Schema::table('stock_movements', function (Blueprint $table) {
            $table->decimal('total_cost', 16, 2)->nullable()->after('unit_cost');
            $table->string('valuation_method', 30)->nullable()->after('total_cost');
            $table->unsignedBigInteger('journal_entry_id')->nullable()->after('notes');
            $table->string('accounting_status', 20)->nullable()->after('journal_entry_id');
            $table->timestamp('accounting_posted_at')->nullable()->after('accounting_status');
            $table->text('accounting_error')->nullable()->after('accounting_posted_at');
            $table->unsignedBigInteger('reversal_journal_entry_id')->nullable()->after('accounting_error');
        });
    }

    public function down(): void
    {
        Schema::table('stock_movements', function (Blueprint $table) {
            $table->dropColumn(['total_cost', 'valuation_method', 'journal_entry_id', 'accounting_status', 'accounting_posted_at', 'accounting_error', 'reversal_journal_entry_id']);
        });
        Schema::table('stock_balances', function (Blueprint $table) {
            $table->dropColumn(['average_cost', 'total_value', 'last_valued_at']);
        });
    }
};
