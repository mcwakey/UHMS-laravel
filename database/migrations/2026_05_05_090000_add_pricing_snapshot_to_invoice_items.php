<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Capture a payer-specific pricing snapshot on each invoice line.
     *
     *   - cash_price            base cash & carry price at the time of billing
     *   - selected_price        actual unit price billed (cash or payer-specific)
     *   - discount_amount       benefit shown to the patient = (cash - selected) * qty
     *   - payer_type            cash | insurance | corporate
     *   - insurance_provider_id which provider's price was used (nullable)
     *   - pricing_source        which row in service_prices was selected
     *
     * The `unit_price` column already exists and is the per-unit selected price,
     * matching `selected_price` on insert. We keep both so future per-line
     * adjustments don't lose the original snapshot.
     */
    public function up(): void
    {
        if (! Schema::hasTable('invoice_items')) {
            return;
        }

        Schema::table('invoice_items', function (Blueprint $table) {
            try { $table->decimal('cash_price', 10, 2)->nullable()->after('unit_price'); } catch (\Throwable $e) {}
            try { $table->decimal('selected_price', 10, 2)->nullable()->after('cash_price'); } catch (\Throwable $e) {}
            try { $table->decimal('discount_amount', 10, 2)->default(0)->after('selected_price'); } catch (\Throwable $e) {}
            try { $table->string('payer_type', 30)->nullable()->after('discount_amount'); } catch (\Throwable $e) {}
            try { $table->unsignedBigInteger('insurance_provider_id')->nullable()->after('payer_type'); } catch (\Throwable $e) {}
            try { $table->string('pricing_source', 64)->nullable()->after('insurance_provider_id'); } catch (\Throwable $e) {}
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('invoice_items')) {
            return;
        }

        Schema::table('invoice_items', function (Blueprint $table) {
            foreach (['cash_price', 'selected_price', 'discount_amount', 'payer_type', 'insurance_provider_id', 'pricing_source'] as $col) {
                try { $table->dropColumn($col); } catch (\Throwable $e) {}
            }
        });
    }
};
