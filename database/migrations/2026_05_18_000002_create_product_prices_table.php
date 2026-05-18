<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Stores per-insurance-type and per-provider product price overrides.
 *
 * Priority during billing resolution:
 *   provider-specific  (insurance_provider_id IS NOT NULL)
 *       ↓
 *   insurance-type default  (insurance_provider_id IS NULL)
 *       ↓
 *   product.base_price  (cash & carry fallback)
 *
 * Mirrors service_prices but for Product instead of ServiceCatalog.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_prices', function (Blueprint $table) {
            $table->id();

            $table->foreignId('product_id')
                ->constrained('products')
                ->cascadeOnDelete();

            // Insurance type value (matches InsuranceType enum: 'self','nhia','private','corporate').
            $table->string('insurance_type', 50);

            // NULL  = default price for the whole insurance type.
            // Non-NULL = provider-specific override (highest priority).
            $table->foreignId('insurance_provider_id')
                ->nullable()
                ->constrained('insurance_providers')
                ->cascadeOnDelete();

            $table->decimal('price', 12, 2);

            // Soft-deactivate without deleting so historical audits are preserved.
            $table->boolean('is_active')->default(true);

            $table->timestamps();

            // Prevents duplicate active price for the same product/type/provider combo.
            // (MySQL treats NULL != NULL in UNIQUE, so NULLs for provider_id are fine.)
            $table->unique(
                ['product_id', 'insurance_type', 'insurance_provider_id'],
                'uq_prod_ins_type_provider'
            );

            $table->index('product_id');
            $table->index(['product_id', 'insurance_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_prices');
    }
};
