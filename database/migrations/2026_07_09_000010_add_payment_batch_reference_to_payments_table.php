<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Cross-visit payment allocation records ONE physical tender as several
 * per-invoice Payment rows (each visit keeps its own clean invoice + journal
 * entry). This nullable reference groups those rows back into a single tender
 * so the cashier, statement and activity log can show them together.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            if (! Schema::hasColumn('payments', 'payment_batch_reference')) {
                $table->string('payment_batch_reference')->nullable()->after('reference_number')->index();
            }
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            if (Schema::hasColumn('payments', 'payment_batch_reference')) {
                $table->dropIndex(['payment_batch_reference']);
                $table->dropColumn('payment_batch_reference');
            }
        });
    }
};
