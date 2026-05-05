<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Add department_id to specialties
        Schema::table('specialties', function (Blueprint $table) {
            $table->foreignId('department_id')->nullable()->after('name')
                ->constrained('departments')->nullOnDelete();
        });

        // Service prices table — default per insurance type + provider-specific overrides
        Schema::create('service_prices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_catalog_id')
                ->constrained('service_catalog')
                ->cascadeOnDelete();
            $table->string('insurance_type', 50); // self, public, private, corporate
            $table->foreignId('insurance_provider_id')->nullable()
                ->constrained('insurance_providers')
                ->cascadeOnDelete();
            $table->decimal('price', 10, 2);
            $table->timestamps();

            // Unique: one price per service × type × provider (null = default for type)
            $table->unique(
                ['service_catalog_id', 'insurance_type', 'insurance_provider_id'],
                'uq_svc_ins_type_provider'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('service_prices');

        Schema::table('specialties', function (Blueprint $table) {
            $table->dropForeign(['department_id']);
            $table->dropColumn('department_id');
        });
    }
};
