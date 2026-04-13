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
            $table->unsignedBigInteger('service_catalog_id');
            $table->foreignId('department_id')->nullable()->constrained()->nullOnDelete();
            $table->integer('quantity')->default(1);
            $table->decimal('unit_price', 10, 2);
            $table->decimal('insurance_price', 10, 2)->nullable();
            $table->decimal('total_price', 10, 2);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->foreign('service_catalog_id')->references('id')->on('service_catalog')->cascadeOnDelete();
            $table->index(['visit_id', 'service_catalog_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('visit_services');
    }
};
