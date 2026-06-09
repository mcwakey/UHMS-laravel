<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private array $tables = [
        'invoices',
        'invoice_items',
        'payments',
        'invoice_discounts',
        'credit_notes',
    ];

    public function up(): void
    {
        foreach ($this->tables as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->foreignId('journal_entry_id')->nullable()->constrained('journal_entries')->nullOnDelete();
                $table->timestamp('accounting_posted_at')->nullable();
                $table->string('accounting_status', 20)->default('pending')->index();
                $table->text('accounting_error')->nullable();
            });
        }
    }

    public function down(): void
    {
        foreach (array_reverse($this->tables) as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->dropForeign(['journal_entry_id']);
                $table->dropColumn([
                    'journal_entry_id',
                    'accounting_posted_at',
                    'accounting_status',
                    'accounting_error',
                ]);
            });
        }
    }
};
