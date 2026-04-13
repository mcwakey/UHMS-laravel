<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('visit_services', function (Blueprint $table) {
            $table->id();
            $table->foreignId('visit_id')->constrained()->cascadeOnDelete();
            $table->foreignId('service_catalog_id')->constrained('service_catalog');
            $table->foreignId('department_id')->nullable()->constrained()->nullOnDelete();
            $table->integer('quantity')->default(1);
            $table->decimal('unit_price', 10, 2);
            $table->decimal('total_price', 10, 2);
            $table->decimal('insurance_covered', 10, 2)->default(0);
            $table->decimal('patient_payable', 10, 2);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('visit_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('visit_services');
    }
};
