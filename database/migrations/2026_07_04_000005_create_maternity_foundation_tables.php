<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pregnancy_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->foreignId('visit_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('admission_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('department_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedTinyInteger('gravida')->nullable();
            $table->unsignedTinyInteger('para')->nullable();
            $table->unsignedTinyInteger('abortions')->nullable();
            $table->unsignedTinyInteger('living_children')->nullable();
            $table->date('last_menstrual_period')->nullable();
            $table->date('estimated_due_date')->nullable();
            $table->unsignedTinyInteger('gestational_age_weeks')->nullable();
            $table->unsignedTinyInteger('gestational_age_days')->nullable();
            $table->string('blood_group')->nullable();
            $table->string('rhesus_status')->nullable();
            $table->json('known_risks')->nullable();
            $table->text('allergies_snapshot')->nullable();
            $table->boolean('previous_caesarean')->default(false);
            $table->boolean('previous_postpartum_haemorrhage')->default(false);
            $table->boolean('hypertensive_disorder_risk')->default(false);
            $table->boolean('diabetes_risk')->default(false);
            $table->boolean('multiple_pregnancy')->default(false);
            $table->string('profile_status')->default('active');
            $table->timestamp('closed_at')->nullable();
            $table->foreignId('closed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('closure_reason')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['patient_id', 'profile_status']);
            $table->index('estimated_due_date');
            $table->index('department_id');
        });

        Schema::create('maternity_cases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pregnancy_profile_id')->nullable()->constrained('pregnancy_profiles')->nullOnDelete();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->foreignId('visit_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('admission_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('department_id')->nullable()->constrained()->nullOnDelete();
            $table->string('source_type')->nullable();
            $table->unsignedBigInteger('source_id')->nullable();
            $table->string('case_type')->nullable();
            $table->string('status')->default('open');
            $table->string('priority')->nullable();
            $table->string('risk_level')->nullable();
            $table->foreignId('opened_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('opened_at')->nullable();
            $table->foreignId('closed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('closed_at')->nullable();
            $table->text('reason')->nullable();
            $table->text('clinical_summary')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['patient_id', 'status']);
            $table->index(['pregnancy_profile_id', 'status']);
            $table->index(['source_type', 'source_id']);
            $table->index('department_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('maternity_cases');
        Schema::dropIfExists('pregnancy_profiles');
    }
};
