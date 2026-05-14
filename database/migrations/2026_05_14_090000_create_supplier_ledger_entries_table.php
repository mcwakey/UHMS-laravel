<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('supplier_ledger_entries')) {
            Schema::create('supplier_ledger_entries', function (Blueprint $table) {
                $table->id();
                $table->foreignId('supplier_id')->constrained('suppliers')->cascadeOnDelete();
                $table->date('entry_date');
                $table->string('entry_type', 50);   // PURCHASE_ORDER / GOODS_RECEIVED / SUPPLIER_INVOICE / PAYMENT / RETURN_TO_SUPPLIER / CREDIT_NOTE / DEBIT_NOTE / ADJUSTMENT
                $table->string('source_type', 191)->nullable();
                $table->unsignedBigInteger('source_id')->nullable();
                $table->string('description', 500);
                $table->decimal('debit',  18, 4)->default(0);
                $table->decimal('credit', 18, 4)->default(0);
                $table->decimal('balance_after', 18, 4)->nullable();
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();

                $table->index(['supplier_id', 'entry_date']);
                $table->index(['source_type', 'source_id']);
                $table->index('entry_type');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('supplier_ledger_entries');
    }
};
