<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ── Theatre rooms catalog ─────────────────────────────────────────
        Schema::create('theatre_rooms', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('location')->nullable();
            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->index('is_active');
        });

        // ── Procedure requests (canonical theatre workflow entity) ────────
        Schema::create('procedure_requests', function (Blueprint $table) {
            $table->id();
            $table->string('request_number')->unique();
            $table->foreignId('visit_id')->constrained('visits')->cascadeOnDelete();
            $table->foreignId('patient_id')->constrained('patients')->cascadeOnDelete();
            $table->foreignId('requested_by')->constrained('users');
            $table->foreignId('department_id')->constrained('departments');
            $table->foreignId('service_catalog_id')->nullable()->constrained('service_catalog')->nullOnDelete();
            $table->foreignId('procedure_id')->nullable()->constrained('procedures')->nullOnDelete();
            $table->string('priority')->default('routine'); // routine, urgent, emergency
            $table->text('indication');
            $table->text('notes')->nullable();
            $table->timestamp('preferred_datetime')->nullable();

            $table->string('status')->default('requested')->index();

            // Billing linkage
            $table->foreignId('billing_item_id')->nullable()->constrained('invoice_items')->nullOnDelete();
            $table->timestamp('billed_at')->nullable();
            $table->foreignId('billed_by')->nullable()->constrained('users')->nullOnDelete();

            // Workflow audit
            $table->foreignId('accepted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('accepted_at')->nullable();
            $table->text('acceptance_notes')->nullable();

            $table->foreignId('rejected_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('rejected_at')->nullable();
            $table->text('rejection_reason')->nullable();

            $table->foreignId('cancelled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('cancelled_at')->nullable();
            $table->text('cancellation_reason')->nullable();

            $table->foreignId('completed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('completed_at')->nullable();

            $table->timestamp('requested_at')->nullable();
            $table->timestamps();

            $table->index(['visit_id', 'status']);
            $table->index(['patient_id', 'status']);
            $table->index('department_id');
        });

        // ── Procedure schedules ───────────────────────────────────────────
        Schema::create('procedure_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('procedure_request_id')->constrained('procedure_requests')->cascadeOnDelete();
            $table->foreignId('theatre_room_id')->nullable()->constrained('theatre_rooms')->nullOnDelete();
            $table->dateTime('scheduled_start');
            $table->dateTime('scheduled_end')->nullable();
            $table->foreignId('surgeon_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('anaesthetist_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('assistant_surgeon_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('theatre_nurse_ids')->nullable();
            $table->text('required_equipment')->nullable();
            $table->string('status')->default('scheduled'); // scheduled, rescheduled, cancelled
            $table->text('notes')->nullable();
            $table->foreignId('scheduled_by')->constrained('users');
            $table->timestamp('scheduled_at')->nullable();
            $table->boolean('is_current')->default(true);
            $table->timestamps();

            $table->index(['procedure_request_id', 'is_current']);
            $table->index('scheduled_start');
        });

        // ── Procedure vitals (multi-stage) ───────────────────────────────
        Schema::create('procedure_vitals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('procedure_request_id')->constrained('procedure_requests')->cascadeOnDelete();
            $table->string('stage'); // pre_op, intra_op, post_op, recovery
            $table->decimal('temperature', 5, 2)->nullable();
            $table->string('blood_pressure', 20)->nullable();
            $table->unsignedSmallInteger('pulse')->nullable();
            $table->unsignedSmallInteger('respiratory_rate')->nullable();
            $table->unsignedSmallInteger('oxygen_saturation')->nullable();
            $table->decimal('weight', 6, 2)->nullable();
            $table->unsignedTinyInteger('pain_score')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('recorded_by')->constrained('users');
            $table->timestamp('recorded_at');
            $table->timestamps();

            $table->index(['procedure_request_id', 'stage']);
        });

        // ── Pre-op checklist (one per request) ────────────────────────────
        Schema::create('procedure_checklists', function (Blueprint $table) {
            $table->id();
            $table->foreignId('procedure_request_id')->constrained('procedure_requests')->cascadeOnDelete()->unique();
            $table->boolean('consent_signed')->default(false);
            $table->boolean('fasting_confirmed')->default(false);
            $table->boolean('allergies_checked')->default(false);
            $table->boolean('blood_available')->default(false);
            $table->boolean('site_marked')->default(false);
            $table->boolean('equipment_ready')->default(false);
            $table->boolean('anaesthesia_review_done')->default(false);
            $table->string('pre_op_diagnosis')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('completed_by')->constrained('users');
            $table->timestamp('completed_at');
            $table->timestamps();
        });

        // ── Anaesthesia notes ─────────────────────────────────────────────
        Schema::create('anaesthesia_notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('procedure_request_id')->constrained('procedure_requests')->cascadeOnDelete()->unique();
            $table->foreignId('anaesthetist_id')->constrained('users');
            $table->string('anaesthesia_type'); // local, regional, spinal, general, sedation, other
            $table->text('pre_assessment')->nullable();
            $table->text('drugs_used')->nullable();
            $table->text('dosage_notes')->nullable();
            $table->text('airway_management')->nullable();
            $table->text('monitoring_notes')->nullable();
            $table->text('complications')->nullable();
            $table->dateTime('start_time')->nullable();
            $table->dateTime('end_time')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        // ── Operative (surgeon) notes ─────────────────────────────────────
        Schema::create('operative_notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('procedure_request_id')->constrained('procedure_requests')->cascadeOnDelete()->unique();
            $table->foreignId('surgeon_id')->constrained('users');
            $table->foreignId('assistant_surgeon_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('procedure_performed');
            $table->text('pre_op_diagnosis')->nullable();
            $table->text('post_op_diagnosis')->nullable();
            $table->text('findings')->nullable();
            $table->text('incision')->nullable();
            $table->text('technique')->nullable();
            $table->string('blood_loss')->nullable();
            $table->text('complications')->nullable();
            $table->text('specimens')->nullable();
            $table->text('implants')->nullable();
            $table->dateTime('start_time')->nullable();
            $table->dateTime('end_time')->nullable();
            $table->string('outcome')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        // ── Post-op / recovery notes ──────────────────────────────────────
        Schema::create('post_op_notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('procedure_request_id')->constrained('procedure_requests')->cascadeOnDelete()->unique();
            $table->foreignId('recorded_by')->constrained('users');
            $table->string('recovery_status')->nullable();
            $table->unsignedTinyInteger('pain_score')->nullable();
            $table->string('consciousness_level')->nullable();
            $table->text('post_op_instructions')->nullable();
            $table->text('medications')->nullable();
            $table->text('complications')->nullable();
            $table->string('transfer_destination')->nullable(); // ward, icu, outpatient, emergency_obs, recovery_room
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        // ── Status change log ─────────────────────────────────────────────
        Schema::create('procedure_status_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('procedure_request_id')->constrained('procedure_requests')->cascadeOnDelete();
            $table->string('from_status')->nullable();
            $table->string('to_status');
            $table->foreignId('changed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('reason')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('procedure_request_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('procedure_status_logs');
        Schema::dropIfExists('post_op_notes');
        Schema::dropIfExists('operative_notes');
        Schema::dropIfExists('anaesthesia_notes');
        Schema::dropIfExists('procedure_checklists');
        Schema::dropIfExists('procedure_vitals');
        Schema::dropIfExists('procedure_schedules');
        Schema::dropIfExists('procedure_requests');
        Schema::dropIfExists('theatre_rooms');
    }
};
