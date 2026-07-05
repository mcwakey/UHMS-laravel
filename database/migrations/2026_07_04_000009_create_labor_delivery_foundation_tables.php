<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('labor_episodes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pregnancy_profile_id')->constrained('pregnancy_profiles')->cascadeOnDelete();
            $table->foreignId('maternity_case_id')->nullable()->constrained('maternity_cases')->nullOnDelete();
            $table->foreignId('antenatal_visit_id')->nullable()->constrained('antenatal_visits')->nullOnDelete();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->foreignId('visit_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('admission_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('department_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('started_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('labor_onset_at')->nullable();
            $table->string('membranes_status')->nullable();
            $table->timestamp('rupture_of_membranes_at')->nullable();
            $table->string('liquor_colour')->nullable();
            $table->string('presentation')->nullable();
            $table->string('fetal_position')->nullable();
            $table->timestamp('contractions_started_at')->nullable();
            $table->string('labor_stage')->default('unknown');
            $table->string('status')->default('active');
            $table->string('risk_level')->nullable();
            $table->string('referral_source')->nullable();
            $table->string('delivery_mode_planned')->nullable();
            $table->boolean('theatre_escalation_required')->default(false);
            $table->boolean('emergency_escalation_required')->default(false);
            $table->text('clinical_summary')->nullable();
            $table->text('initial_assessment')->nullable();
            $table->json('complications')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->foreignId('closed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('closure_reason')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['pregnancy_profile_id', 'status']);
            $table->index(['patient_id', 'status']);
            $table->index(['admission_id', 'status']);
            $table->index('labor_stage');
            $table->index('started_at');
        });

        Schema::create('labor_observations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('labor_episode_id')->constrained('labor_episodes')->cascadeOnDelete();
            $table->foreignId('pregnancy_profile_id')->constrained('pregnancy_profiles')->cascadeOnDelete();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->foreignId('visit_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('admission_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('observed_at');
            $table->string('labor_stage')->nullable();
            $table->decimal('cervical_dilation_cm', 4, 1)->nullable();
            $table->unsignedSmallInteger('fetal_heart_rate')->nullable();
            $table->unsignedTinyInteger('contractions_per_10_min')->nullable();
            $table->unsignedSmallInteger('contraction_duration_seconds')->nullable();
            $table->string('descent')->nullable();
            $table->string('moulding')->nullable();
            $table->string('caput')->nullable();
            $table->string('membranes_status')->nullable();
            $table->string('liquor_colour')->nullable();
            $table->unsignedSmallInteger('maternal_pulse')->nullable();
            $table->unsignedSmallInteger('blood_pressure_systolic')->nullable();
            $table->unsignedSmallInteger('blood_pressure_diastolic')->nullable();
            $table->decimal('temperature', 4, 1)->nullable();
            $table->unsignedSmallInteger('respiratory_rate')->nullable();
            $table->string('urine_protein')->nullable();
            $table->string('urine_glucose')->nullable();
            $table->unsignedSmallInteger('urine_volume_ml')->nullable();
            $table->unsignedTinyInteger('pain_score')->nullable();
            $table->text('oxytocin')->nullable();
            $table->text('fluids')->nullable();
            $table->text('medication')->nullable();
            $table->json('risk_flags')->nullable();
            $table->json('danger_signs')->nullable();
            $table->text('notes')->nullable();
            $table->string('status')->default('recorded');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['labor_episode_id', 'observed_at']);
            $table->index(['pregnancy_profile_id', 'observed_at']);
            $table->index(['patient_id', 'status']);
            $table->index('status');
        });

        Schema::create('delivery_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('labor_episode_id')->constrained('labor_episodes')->cascadeOnDelete();
            $table->foreignId('pregnancy_profile_id')->constrained('pregnancy_profiles')->cascadeOnDelete();
            $table->foreignId('maternity_case_id')->nullable()->constrained('maternity_cases')->nullOnDelete();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->foreignId('visit_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('admission_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('department_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('delivery_at')->nullable();
            $table->string('delivery_mode')->nullable();
            $table->string('delivery_outcome')->nullable();
            $table->string('placenta_status')->nullable();
            $table->unsignedInteger('estimated_blood_loss_ml')->nullable();
            $table->string('maternal_condition')->nullable();
            $table->json('complications')->nullable();
            $table->foreignId('attending_staff_id')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedBigInteger('theatre_case_id')->nullable();
            $table->unsignedBigInteger('emergency_case_id')->nullable();
            $table->unsignedTinyInteger('newborn_count')->nullable()->default(1);
            $table->boolean('newborn_records_pending')->default(true);
            $table->text('notes')->nullable();
            $table->string('status')->default('draft');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['labor_episode_id', 'status']);
            $table->index(['pregnancy_profile_id', 'delivery_at']);
            $table->index(['patient_id', 'status']);
            $table->index('delivery_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('delivery_records');
        Schema::dropIfExists('labor_observations');
        Schema::dropIfExists('labor_episodes');
    }
};
