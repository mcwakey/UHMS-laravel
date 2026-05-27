<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('medication_frequencies')) Schema::create('medication_frequencies', function (Blueprint $table) {
            $table->id();
            $table->string('code', 40)->unique();
            $table->string('name');
            $table->unsignedSmallInteger('times_per_day')->nullable();
            $table->unsignedSmallInteger('interval_hours')->nullable();
            $table->text('default_times')->nullable();
            $table->boolean('requires_schedule')->default(true);
            $table->boolean('is_prn')->default(false);
            $table->boolean('is_stat')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['is_active', 'code']);
        });

        if (! Schema::hasTable('medication_orders')) Schema::create('medication_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('visit_id')->constrained('visits')->cascadeOnDelete();
            $table->foreignId('admission_id')->nullable()->constrained('admissions')->nullOnDelete();
            $table->unsignedBigInteger('emergency_case_id')->nullable();
            $table->foreignId('patient_id')->constrained('patients')->cascadeOnDelete();
            $table->foreignId('medical_record_id')->nullable()->constrained('medical_records')->nullOnDelete();
            $table->foreignId('consultation_route_id')->nullable()->constrained('visit_consultation_routes')->nullOnDelete();
            $table->foreignId('prescription_id')->nullable()->constrained('prescriptions')->nullOnDelete();
            $table->foreignId('prescription_item_id')->nullable()->constrained('prescription_items')->nullOnDelete();
            $table->foreignId('prescribed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('product_id')->nullable()->constrained('products')->nullOnDelete();
            $table->foreignId('drug_id')->nullable()->constrained('drugs')->nullOnDelete();
            $table->string('drug_name')->nullable();
            $table->string('dose')->nullable();
            $table->string('dose_unit', 40)->nullable();
            $table->string('route', 80)->nullable();
            $table->foreignId('frequency_id')->nullable()->constrained('medication_frequencies')->nullOnDelete();
            $table->string('frequency_code', 40)->nullable();
            $table->unsignedInteger('duration_value')->nullable();
            $table->string('duration_unit', 30)->nullable();
            $table->unsignedInteger('total_doses')->default(0);
            $table->decimal('quantity_ordered', 14, 4)->default(0);
            $table->decimal('quantity_dispensed', 14, 4)->default(0);
            $table->dateTime('start_at')->nullable();
            $table->dateTime('end_at')->nullable();
            $table->text('instructions')->nullable();
            $table->string('status', 60)->default('PENDING_DISPENSING');
            $table->string('source_type', 100)->nullable();
            $table->unsignedBigInteger('source_id')->nullable();
            $table->timestamps();

            $table->unique('prescription_item_id', 'medication_orders_prescription_item_unique');
            $table->index(['visit_id', 'status']);
            $table->index(['admission_id', 'status']);
            $table->index(['emergency_case_id', 'status']);
            $table->index(['patient_id', 'status']);
            $table->index(['source_type', 'source_id']);
        });

        if (! Schema::hasTable('clinical_tasks')) Schema::create('clinical_tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('visit_id')->nullable()->constrained('visits')->cascadeOnDelete();
            $table->foreignId('admission_id')->nullable()->constrained('admissions')->nullOnDelete();
            $table->unsignedBigInteger('emergency_case_id')->nullable();
            $table->foreignId('patient_id')->nullable()->constrained('patients')->cascadeOnDelete();
            $table->string('task_type', 80);
            $table->string('title');
            $table->text('description')->nullable();
            $table->dateTime('scheduled_at')->nullable();
            $table->dateTime('due_at')->nullable();
            $table->string('status', 60)->default('SCHEDULED');
            $table->string('priority', 30)->default('normal');
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->string('assigned_role', 80)->nullable();
            $table->foreignId('assigned_department_id')->nullable()->constrained('departments')->nullOnDelete();
            $table->string('source_type', 100)->nullable();
            $table->unsignedBigInteger('source_id')->nullable();
            $table->foreignId('completed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('completed_at')->nullable();
            $table->text('notes')->nullable();
            $table->unsignedSmallInteger('escalation_level')->default(0);
            $table->dateTime('last_reminded_at')->nullable();
            $table->timestamps();

            $table->index(['task_type', 'status']);
            $table->index(['due_at', 'status']);
            $table->index(['admission_id', 'task_type', 'status']);
            $table->index(['emergency_case_id', 'task_type', 'status']);
            $table->index(['assigned_department_id', 'status']);
            $table->unique(['task_type', 'source_type', 'source_id'], 'clinical_tasks_source_unique');
        });

        if (! Schema::hasTable('medication_administration_schedules')) Schema::create('medication_administration_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('medication_order_id')->constrained('medication_orders')->cascadeOnDelete();
            $table->foreignId('visit_id')->constrained('visits')->cascadeOnDelete();
            $table->foreignId('admission_id')->nullable()->constrained('admissions')->nullOnDelete();
            $table->unsignedBigInteger('emergency_case_id')->nullable();
            $table->foreignId('patient_id')->constrained('patients')->cascadeOnDelete();
            $table->dateTime('scheduled_at');
            $table->string('dose')->nullable();
            $table->string('dose_unit', 40)->nullable();
            $table->string('route', 80)->nullable();
            $table->string('status', 60)->default('SCHEDULED');
            $table->unsignedInteger('sequence_number');
            $table->foreignId('clinical_task_id')->nullable()->constrained('clinical_tasks')->nullOnDelete();
            $table->timestamps();

            $table->unique(['medication_order_id', 'sequence_number'], 'med_admin_sched_order_sequence_unique');
            $table->index(['scheduled_at', 'status']);
            // $table->index(['admission_id', 'status', 'scheduled_at']);
            // $table->index(['emergency_case_id', 'status', 'scheduled_at']);
            // $table->index(['scheduled_at', 'status']);
            $table->index(['admission_id', 'status', 'scheduled_at'], 'mas_admission_status_scheduled_idx');
            $table->index(['emergency_case_id', 'status', 'scheduled_at'], 'mas_emergency_status_scheduled_idx');

        });

        if (! Schema::hasTable('medication_administrations')) Schema::create('medication_administrations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('medication_order_id')->constrained('medication_orders')->cascadeOnDelete();
            $table->foreignId('schedule_id')->nullable()->constrained('medication_administration_schedules')->nullOnDelete();
            $table->foreignId('clinical_task_id')->nullable()->constrained('clinical_tasks')->nullOnDelete();
            $table->foreignId('visit_id')->constrained('visits')->cascadeOnDelete();
            $table->foreignId('admission_id')->nullable()->constrained('admissions')->nullOnDelete();
            $table->unsignedBigInteger('emergency_case_id')->nullable();
            $table->foreignId('patient_id')->constrained('patients')->cascadeOnDelete();
            $table->foreignId('administered_by')->constrained('users')->cascadeOnDelete();
            $table->dateTime('administered_at');
            $table->dateTime('scheduled_at')->nullable();
            $table->string('dose_given')->nullable();
            $table->string('dose_unit', 40)->nullable();
            $table->string('route', 80)->nullable();
            $table->string('status', 60);
            $table->text('reason_not_given')->nullable();
            $table->text('notes')->nullable();
            $table->text('reaction')->nullable();
            $table->string('source_stock_type', 80)->default('PATIENT_DISPENSED_STOCK');
            $table->foreignId('stock_location_id')->nullable()->constrained('stock_locations')->nullOnDelete();
            $table->foreignId('stock_movement_id')->nullable()->constrained('stock_movements')->nullOnDelete();
            $table->foreignId('witnessed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('corrected_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('corrected_at')->nullable();
            $table->text('correction_reason')->nullable();
            $table->timestamps();

            $table->unique('schedule_id', 'medication_administrations_schedule_unique');
            $table->index(['medication_order_id', 'status']);
            $table->index(['administered_by', 'administered_at']);
            $table->index(['admission_id', 'administered_at']);
            $table->index(['emergency_case_id', 'administered_at']);
        });

        if (! Schema::hasTable('medication_administration_logs')) Schema::create('medication_administration_logs', function (Blueprint $table) {
            $table->id();
            // $table->foreignId('medication_administration_id')->nullable()->constrained('medication_administrations')->nullOnDelete();
            $table->foreignId('medication_administration_id')->nullable()->constrained('medication_administrations', 'id', 'mal_med_admin_id_foreign')->nullOnDelete();
            $table->foreignId('medication_order_id')->nullable()->constrained('medication_orders')->nullOnDelete();
            $table->foreignId('schedule_id')->nullable()->constrained('medication_administration_schedules')->nullOnDelete();
            $table->string('action', 80);
            $table->text('old_value')->nullable();
            $table->text('new_value')->nullable();
            $table->text('reason')->nullable();
            $table->foreignId('performed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['medication_order_id', 'action']);
            $table->index(['schedule_id', 'action']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('medication_administration_logs');
        Schema::dropIfExists('medication_administrations');
        Schema::dropIfExists('medication_administration_schedules');
        Schema::dropIfExists('clinical_tasks');
        Schema::dropIfExists('medication_orders');
        Schema::dropIfExists('medication_frequencies');
    }
};
