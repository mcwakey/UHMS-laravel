<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('newborn_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('delivery_record_id')->constrained('delivery_records')->cascadeOnDelete();
            $table->foreignId('labor_episode_id')->constrained('labor_episodes')->cascadeOnDelete();
            $table->foreignId('pregnancy_profile_id')->constrained('pregnancy_profiles')->cascadeOnDelete();
            $table->foreignId('maternity_case_id')->nullable()->constrained('maternity_cases')->nullOnDelete();
            $table->foreignId('mother_patient_id')->constrained('patients')->cascadeOnDelete();
            $table->foreignId('newborn_patient_id')->nullable()->constrained('patients')->nullOnDelete();
            $table->foreignId('visit_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('admission_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('department_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedInteger('baby_number')->nullable();
            $table->unsignedInteger('birth_order')->nullable();
            $table->string('sex')->nullable();
            $table->timestamp('birth_time')->nullable();
            $table->decimal('birth_weight_kg', 5, 3)->nullable();
            $table->decimal('length_cm', 5, 2)->nullable();
            $table->decimal('head_circumference_cm', 5, 2)->nullable();
            $table->unsignedTinyInteger('apgar_1_min')->nullable();
            $table->unsignedTinyInteger('apgar_5_min')->nullable();
            $table->unsignedTinyInteger('apgar_10_min')->nullable();
            $table->boolean('cried_at_birth')->nullable();
            $table->boolean('resuscitation_required')->default(false);
            $table->text('resuscitation_details')->nullable();
            $table->text('congenital_concerns')->nullable();
            $table->string('feeding_status')->nullable();
            $table->decimal('temperature', 4, 1)->nullable();
            $table->string('breathing_status')->nullable();
            $table->string('cord_status')->nullable();
            $table->string('colour')->nullable();
            $table->json('risk_flags')->nullable();
            $table->json('danger_signs')->nullable();
            $table->string('neonatal_condition')->nullable();
            $table->string('outcome')->nullable();
            $table->string('status')->default('active');
            $table->string('transferred_to')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('closed_at')->nullable();
            $table->foreignId('closed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('closure_reason')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['delivery_record_id', 'birth_order']);
            $table->index(['delivery_record_id', 'status']);
            $table->index(['pregnancy_profile_id', 'status']);
            $table->index(['mother_patient_id', 'status']);
            $table->index(['newborn_patient_id']);
            $table->index('birth_time');
            $table->index('outcome');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('newborn_records');
    }
};
