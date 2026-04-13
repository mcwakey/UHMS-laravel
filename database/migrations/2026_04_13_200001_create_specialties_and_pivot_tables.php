<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Core specialties table
        Schema::create('specialties', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->unique()->nullable();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Doctors ↔ Specialties (many-to-many)
        Schema::create('doctor_specialty', function (Blueprint $table) {
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('specialty_id')->constrained()->cascadeOnDelete();
            $table->primary(['user_id', 'specialty_id']);
        });

        // Services ↔ Specialties (many-to-many)
        Schema::create('service_specialty', function (Blueprint $table) {
            $table->foreignId('service_catalog_id')->constrained('service_catalog')->cascadeOnDelete();
            $table->foreignId('specialty_id')->constrained()->cascadeOnDelete();
            $table->primary(['service_catalog_id', 'specialty_id']);
        });

        // Departments ↔ Specialties (many-to-many)
        Schema::create('department_specialty', function (Blueprint $table) {
            $table->foreignId('department_id')->constrained()->cascadeOnDelete();
            $table->foreignId('specialty_id')->constrained()->cascadeOnDelete();
            $table->primary(['department_id', 'specialty_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('department_specialty');
        Schema::dropIfExists('service_specialty');
        Schema::dropIfExists('doctor_specialty');
        Schema::dropIfExists('specialties');
    }
};
