<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('patients', function (Blueprint $table) {
            if (empty(DB::select("SHOW COLUMNS FROM `patients` LIKE 'is_temporary'"))) {
                $table->boolean('is_temporary')->default(false)->after('status');
                $table->string('temporary_reason')->nullable()->after('is_temporary');
                $table->timestamp('identity_confirmed_at')->nullable()->after('temporary_reason');
                $table->foreignId('identity_confirmed_by')->nullable()->after('identity_confirmed_at')->constrained('users')->nullOnDelete();
                $table->foreignId('merged_to_patient_id')->nullable()->after('identity_confirmed_by')->constrained('patients')->nullOnDelete();
            }
        });

        if (! Schema::hasTable('emergency_bays')) {
            Schema::create('emergency_bays', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('code')->unique();
                $table->string('bay_type', 60)->default('TREATMENT');
                $table->foreignId('department_id')->nullable()->constrained('departments')->nullOnDelete();
                $table->string('status', 40)->default('AVAILABLE');
                $table->text('notes')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();

                $table->index(['status', 'is_active']);
                $table->index(['bay_type', 'status']);
            });
        }

        if (! Schema::hasTable('emergency_cases')) {
            Schema::create('emergency_cases', function (Blueprint $table) {
                $table->id();
                $table->string('emergency_number')->unique();
                $table->foreignId('visit_id')->constrained('visits')->cascadeOnDelete();
                $table->foreignId('patient_id')->constrained('patients')->cascadeOnDelete();
                $table->foreignId('admission_id')->nullable()->constrained('admissions')->nullOnDelete();
                $table->foreignId('emergency_bay_id')->nullable()->constrained('emergency_bays')->nullOnDelete();
                $table->string('arrival_mode', 60);
                $table->dateTime('arrival_time');
                $table->string('brought_by')->nullable();
                $table->string('source')->nullable();
                $table->string('referral_facility')->nullable();
                $table->text('chief_complaint')->nullable();
                $table->text('initial_condition')->nullable();
                $table->string('triage_category', 20)->nullable();
                $table->unsignedSmallInteger('triage_score')->nullable();
                $table->text('triage_notes')->nullable();
                $table->string('emergency_status', 60)->default('ARRIVED');
                $table->foreignId('assigned_doctor_id')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('assigned_nurse_id')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('triaged_by')->nullable()->constrained('users')->nullOnDelete();
                $table->dateTime('triaged_at')->nullable();
                $table->string('disposition', 80)->nullable();
                $table->text('disposition_notes')->nullable();
                $table->dateTime('disposition_time')->nullable();
                $table->foreignId('disposed_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();

                $table->index('patient_id');
                $table->index('visit_id');
                $table->index('emergency_status');
                $table->index('triage_category');
                $table->index('arrival_time');
                $table->index('emergency_bay_id');
                $table->index(['assigned_doctor_id', 'emergency_status']);
                $table->index(['assigned_nurse_id', 'emergency_status']);
            });
        }

        if (! Schema::hasTable('emergency_case_logs')) {
            Schema::create('emergency_case_logs', function (Blueprint $table) {
                $table->id();
                $table->foreignId('emergency_case_id')->constrained('emergency_cases')->cascadeOnDelete();
                $table->foreignId('visit_id')->nullable()->constrained('visits')->cascadeOnDelete();
                $table->foreignId('patient_id')->nullable()->constrained('patients')->cascadeOnDelete();
                $table->string('action', 80);
                $table->string('title');
                $table->text('description')->nullable();
                $table->string('source_type', 120)->nullable();
                $table->unsignedBigInteger('source_id')->nullable();
                $table->foreignId('performed_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();

                $table->index(['emergency_case_id', 'created_at']);
                $table->index(['source_type', 'source_id']);
            });
        }

        if (! Schema::hasTable('emergency_notes')) {
            Schema::create('emergency_notes', function (Blueprint $table) {
                $table->id();
                $table->foreignId('emergency_case_id')->constrained('emergency_cases')->cascadeOnDelete();
                $table->foreignId('visit_id')->constrained('visits')->cascadeOnDelete();
                $table->foreignId('patient_id')->constrained('patients')->cascadeOnDelete();
                $table->string('note_type', 80)->default('GENERAL_NOTE');
                $table->text('content');
                $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
                $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();

                $table->index(['emergency_case_id', 'created_at']);
                $table->index(['note_type', 'created_at']);
            });
        }

        Schema::table('vitals', function (Blueprint $table) {
            if (empty(DB::select("SHOW COLUMNS FROM `vitals` LIKE 'emergency_case_id'"))) {
                $table->foreignId('emergency_case_id')->nullable()->after('admission_id')->constrained('emergency_cases')->nullOnDelete();
                $table->string('monitoring_context', 80)->nullable()->after('triage_id');
                $table->index(['emergency_case_id', 'recorded_at'], 'vitals_emergency_recorded_idx');
            }
        });

        Schema::table('lab_requests', function (Blueprint $table) {
            if (empty(DB::select("SHOW COLUMNS FROM `lab_requests` LIKE 'emergency_case_id'"))) {
                $table->foreignId('emergency_case_id')->nullable()->after('visit_id')->constrained('emergency_cases')->nullOnDelete();
                $table->boolean('is_emergency')->default(false)->after('urgency');
                $table->index(['emergency_case_id', 'urgency'], 'lab_requests_emergency_urgency_idx');
            }
        });

        Schema::table('procedure_requests', function (Blueprint $table) {
            if (empty(DB::select("SHOW COLUMNS FROM `procedure_requests` LIKE 'emergency_case_id'"))) {
                $table->foreignId('emergency_case_id')->nullable()->after('visit_id')->constrained('emergency_cases')->nullOnDelete();
                $table->boolean('is_emergency')->default(false)->after('priority');
                $table->dateTime('performed_at')->nullable()->after('completed_at');
                $table->foreignId('performed_by')->nullable()->after('performed_at')->constrained('users')->nullOnDelete();
                $table->index(['emergency_case_id', 'priority'], 'procedure_requests_emergency_priority_idx');
            }
        });
    }

    public function down(): void
    {
        Schema::table('procedure_requests', function (Blueprint $table) {
            if (! empty(DB::select("SHOW COLUMNS FROM `procedure_requests` LIKE 'emergency_case_id'"))) {
                $table->dropIndex('procedure_requests_emergency_priority_idx');
                $table->dropConstrainedForeignId('performed_by');
                $table->dropConstrainedForeignId('emergency_case_id');
                $table->dropColumn(['is_emergency', 'performed_at']);
            }
        });

        Schema::table('lab_requests', function (Blueprint $table) {
            if (! empty(DB::select("SHOW COLUMNS FROM `lab_requests` LIKE 'emergency_case_id'"))) {
                $table->dropIndex('lab_requests_emergency_urgency_idx');
                $table->dropConstrainedForeignId('emergency_case_id');
                $table->dropColumn('is_emergency');
            }
        });

        Schema::table('vitals', function (Blueprint $table) {
            if (! empty(DB::select("SHOW COLUMNS FROM `vitals` LIKE 'emergency_case_id'"))) {
                $table->dropIndex('vitals_emergency_recorded_idx');
                $table->dropConstrainedForeignId('emergency_case_id');
                $table->dropColumn('monitoring_context');
            }
        });

        Schema::dropIfExists('emergency_notes');
        Schema::dropIfExists('emergency_case_logs');
        Schema::dropIfExists('emergency_cases');
        Schema::dropIfExists('emergency_bays');

        Schema::table('patients', function (Blueprint $table) {
            if (! empty(DB::select("SHOW COLUMNS FROM `patients` LIKE 'is_temporary'"))) {
                $table->dropConstrainedForeignId('merged_to_patient_id');
                $table->dropConstrainedForeignId('identity_confirmed_by');
                $table->dropColumn(['is_temporary', 'temporary_reason', 'identity_confirmed_at']);
            }
        });
    }
};
