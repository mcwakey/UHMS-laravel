<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('antenatal_visits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pregnancy_profile_id')->constrained('pregnancy_profiles')->cascadeOnDelete();
            $table->foreignId('maternity_case_id')->nullable()->constrained('maternity_cases')->nullOnDelete();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->foreignId('visit_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('admission_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('department_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedInteger('visit_number')->nullable();
            $table->dateTime('visit_date');
            $table->unsignedTinyInteger('gestational_age_weeks')->nullable();
            $table->unsignedTinyInteger('gestational_age_days')->nullable();
            $table->decimal('weight_kg', 6, 2)->nullable();
            $table->unsignedSmallInteger('blood_pressure_systolic')->nullable();
            $table->unsignedSmallInteger('blood_pressure_diastolic')->nullable();
            $table->unsignedSmallInteger('pulse')->nullable();
            $table->decimal('temperature', 4, 1)->nullable();
            $table->unsignedSmallInteger('respiratory_rate')->nullable();
            $table->decimal('fundal_height_cm', 5, 2)->nullable();
            $table->unsignedSmallInteger('fetal_heart_rate')->nullable();
            $table->string('fetal_movement')->nullable();
            $table->string('presentation')->nullable();
            $table->string('urine_protein')->nullable();
            $table->string('urine_glucose')->nullable();
            $table->string('oedema')->nullable();
            $table->decimal('haemoglobin', 4, 1)->nullable();
            $table->json('danger_signs')->nullable();
            $table->json('risk_flags')->nullable();
            $table->text('assessment')->nullable();
            $table->text('plan')->nullable();
            $table->text('counselling')->nullable();
            $table->json('supplements')->nullable();
            $table->json('immunisations')->nullable();
            $table->date('next_visit_date')->nullable();
            $table->string('referral_type')->nullable();
            $table->text('referral_reason')->nullable();
            $table->string('status')->default('recorded');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['pregnancy_profile_id', 'visit_date']);
            $table->index(['patient_id', 'status']);
            $table->index('next_visit_date');
            $table->index('referral_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('antenatal_visits');
    }
};
