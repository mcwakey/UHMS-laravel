<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('insurance_usages') || Schema::hasColumn('insurance_usages', 'invoice_item_id')) {
            return;
        }

        Schema::table('insurance_usages', function (Blueprint $table) {
            $table->foreignId('invoice_item_id')
                ->nullable()
                ->after('invoice_id')
                ->constrained('invoice_items')
                ->nullOnDelete();

            $table->index(['patient_insurance_id', 'invoice_item_id'], 'insurance_usages_insurance_item_idx');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('insurance_usages') || ! Schema::hasColumn('insurance_usages', 'invoice_item_id')) {
            return;
        }

        Schema::table('insurance_usages', function (Blueprint $table) {
            $table->dropIndex('insurance_usages_insurance_item_idx');
            $table->dropForeign(['invoice_item_id']);
            $table->dropColumn('invoice_item_id');
        });
    }
};
