<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('admissions', function (Blueprint $table) {
            $table->timestamp('discharge_planning_started_at')->nullable()->after('care_flags');
            $table->foreignId('discharge_planning_started_by')->nullable()->after('discharge_planning_started_at')->constrained('users')->nullOnDelete();
            $table->timestamp('expected_discharge_at')->nullable()->after('discharge_planning_started_by');
            $table->text('discharge_planning_note')->nullable()->after('expected_discharge_at');
        });

        Schema::create('admission_discharge_clearances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('admission_id')->constrained()->cascadeOnDelete();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->foreignId('visit_id')->nullable()->constrained()->nullOnDelete();
            $table->string('clearance_type');
            $table->string('status')->default('pending');
            $table->foreignId('cleared_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('cleared_at')->nullable();
            $table->foreignId('revoked_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('revoked_at')->nullable();
            $table->text('note')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->unique(['admission_id', 'clearance_type']);
            $table->index(['admission_id', 'status']);
            $table->index('clearance_type');
        });

        Schema::create('admission_discharge_summaries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('admission_id')->constrained()->cascadeOnDelete();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->foreignId('visit_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('prepared_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->string('primary_diagnosis')->nullable();
            $table->json('secondary_diagnoses')->nullable();
            $table->text('admission_reason')->nullable();
            $table->text('hospital_course')->nullable();
            $table->text('investigations_summary')->nullable();
            $table->text('procedures_summary')->nullable();
            $table->text('treatment_given')->nullable();
            $table->string('discharge_condition')->nullable();
            $table->text('discharge_medications')->nullable();
            $table->text('follow_up_instructions')->nullable();
            $table->date('follow_up_date')->nullable();
            $table->text('warning_signs')->nullable();
            $table->string('final_outcome')->nullable();
            $table->string('summary_status')->default('draft');
            $table->timestamps();

            $table->unique('admission_id');
            $table->index(['summary_status', 'approved_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admission_discharge_summaries');
        Schema::dropIfExists('admission_discharge_clearances');

        Schema::table('admissions', function (Blueprint $table) {
            $table->dropConstrainedForeignId('discharge_planning_started_by');
            $table->dropColumn([
                'discharge_planning_started_at',
                'expected_discharge_at',
                'discharge_planning_note',
            ]);
        });
    }
};
