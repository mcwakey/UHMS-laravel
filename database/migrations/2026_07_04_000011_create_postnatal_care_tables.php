<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('postnatal_cases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('delivery_record_id')->constrained()->cascadeOnDelete();
            $table->foreignId('labor_episode_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('pregnancy_profile_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('maternity_case_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('mother_patient_id')->constrained('patients')->cascadeOnDelete();
            $table->foreignId('visit_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('admission_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('department_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('opened_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('closed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('mother_ready_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('newborn_ready_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('ready_for_discharge_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('referral_marked_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('opened_at')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->timestamp('mother_ready_at')->nullable();
            $table->timestamp('newborn_ready_at')->nullable();
            $table->timestamp('ready_for_discharge_at')->nullable();
            $table->timestamp('referral_marked_at')->nullable();
            $table->string('status')->default('open')->index();
            $table->string('risk_level')->default('low')->index();
            $table->boolean('referral_required')->default(false)->index();
            $table->text('referral_reason')->nullable();
            $table->date('follow_up_date')->nullable()->index();
            $table->text('follow_up_instructions')->nullable();
            $table->text('notes')->nullable();
            $table->text('closure_reason')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique('delivery_record_id');
            $table->index(['admission_id', 'status']);
            $table->index(['mother_patient_id', 'status']);
        });

        Schema::create('postnatal_mother_observations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('postnatal_case_id')->constrained()->cascadeOnDelete();
            $table->foreignId('delivery_record_id')->constrained()->cascadeOnDelete();
            $table->foreignId('pregnancy_profile_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('mother_patient_id')->constrained('patients')->cascadeOnDelete();
            $table->foreignId('visit_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('admission_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('department_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('observed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('cancelled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('observed_at')->nullable()->index();
            $table->unsignedSmallInteger('blood_pressure_systolic')->nullable();
            $table->unsignedSmallInteger('blood_pressure_diastolic')->nullable();
            $table->unsignedSmallInteger('pulse')->nullable();
            $table->decimal('temperature', 4, 1)->nullable();
            $table->unsignedSmallInteger('respiratory_rate')->nullable();
            $table->string('bleeding_status')->nullable();
            $table->string('uterus_condition')->nullable();
            $table->unsignedTinyInteger('pain_score')->nullable();
            $table->string('wound_condition')->nullable();
            $table->string('breastfeeding_status')->nullable();
            $table->string('mobility')->nullable();
            $table->string('urination')->nullable();
            $table->text('mental_wellbeing_note')->nullable();
            $table->json('danger_signs')->nullable();
            $table->json('risk_flags')->nullable();
            $table->text('assessment')->nullable();
            $table->text('plan')->nullable();
            $table->text('counselling')->nullable();
            $table->string('status')->default('recorded')->index();
            $table->timestamp('cancelled_at')->nullable();
            $table->text('cancellation_reason')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('postnatal_newborn_observations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('postnatal_case_id')->constrained()->cascadeOnDelete();
            $table->foreignId('newborn_record_id')->constrained()->cascadeOnDelete();
            $table->foreignId('delivery_record_id')->constrained()->cascadeOnDelete();
            $table->foreignId('pregnancy_profile_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('mother_patient_id')->constrained('patients')->cascadeOnDelete();
            $table->foreignId('newborn_patient_id')->nullable()->constrained('patients')->nullOnDelete();
            $table->foreignId('visit_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('admission_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('department_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('observed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('cancelled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('observed_at')->nullable()->index();
            $table->decimal('temperature', 4, 1)->nullable();
            $table->decimal('weight_kg', 5, 2)->nullable();
            $table->string('feeding_status')->nullable();
            $table->string('breathing_status')->nullable();
            $table->string('cord_status')->nullable();
            $table->string('jaundice_status')->nullable();
            $table->string('stooling')->nullable();
            $table->string('urination')->nullable();
            $table->string('activity')->nullable();
            $table->json('danger_signs')->nullable();
            $table->json('risk_flags')->nullable();
            $table->text('immunisation_note')->nullable();
            $table->text('assessment')->nullable();
            $table->text('plan')->nullable();
            $table->text('counselling')->nullable();
            $table->string('status')->default('recorded')->index();
            $table->timestamp('cancelled_at')->nullable();
            $table->text('cancellation_reason')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('postnatal_newborn_observations');
        Schema::dropIfExists('postnatal_mother_observations');
        Schema::dropIfExists('postnatal_cases');
    }
};
