<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('triages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('visit_id')->unique()->constrained()->cascadeOnDelete();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();

            // Vitals
            $table->integer('blood_pressure_systolic')->nullable();
            $table->integer('blood_pressure_diastolic')->nullable();
            $table->integer('heart_rate')->nullable();       // pulse
            $table->decimal('temperature', 4, 1)->nullable(); // °C
            $table->integer('respiratory_rate')->nullable();
            $table->integer('spo2')->nullable();              // oxygen_saturation %
            $table->decimal('weight', 5, 1)->nullable();
            $table->decimal('height', 5, 1)->nullable();
            $table->decimal('bmi', 4, 1)->nullable();

            // Triage outcome
            $table->string('triage_score')->nullable();       // TriageScore enum

            // Assigned consultation department (selected during triage action)
            $table->foreignId('department_id')->nullable()->constrained()->nullOnDelete();

            $table->text('notes')->nullable();
            $table->foreignId('triaged_by')->constrained('users')->cascadeOnDelete();
            $table->timestamp('triaged_at')->useCurrent();
            $table->timestamps();

            $table->index('triage_score');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('triages');
    }
};
