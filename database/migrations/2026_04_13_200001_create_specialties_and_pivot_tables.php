<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Specialties master table
        Schema::create('specialties', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Doctor ↔ Specialty pivot
        Schema::create('doctor_specialty', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('specialty_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['user_id', 'specialty_id']);
        });

        // Service ↔ Specialty pivot
        Schema::create('service_specialty', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('service_catalog_id');
            $table->foreignId('specialty_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->foreign('service_catalog_id')->references('id')->on('service_catalog')->cascadeOnDelete();
            $table->unique(['service_catalog_id', 'specialty_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('service_specialty');
        Schema::dropIfExists('doctor_specialty');
        Schema::dropIfExists('specialties');
    }
};
