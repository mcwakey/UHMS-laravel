<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('blood_donors', function (Blueprint $table) {
            $table->id();
            $table->string('donor_number')->unique();
            $table->foreignId('patient_id')->nullable()->constrained()->nullOnDelete();
            $table->string('first_name');
            $table->string('last_name');
            $table->string('gender')->nullable();
            $table->date('date_of_birth')->nullable();
            $table->string('blood_group')->nullable();
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->text('address')->nullable();
            $table->dateTime('last_donation_at')->nullable();
            $table->string('status')->default('ACTIVE');
            $table->text('deferral_reason')->nullable();
            $table->date('deferred_until')->nullable();
            $table->foreignId('registered_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['blood_group', 'status']);
        });

        Schema::create('blood_storage_locations', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->unique();
            $table->string('location_type')->default('BLOOD_BANK');
            $table->decimal('temperature_min', 5, 2)->nullable();
            $table->decimal('temperature_max', 5, 2)->nullable();
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('blood_donations', function (Blueprint $table) {
            $table->id();
            $table->string('donation_number')->unique();
            $table->foreignId('donor_id')->constrained('blood_donors')->cascadeOnDelete();
            $table->foreignId('collected_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('donation_date');
            $table->string('donation_type')->default('WHOLE_BLOOD');
            $table->unsignedSmallInteger('volume_ml')->nullable();
            $table->string('blood_group');
            $table->string('screening_status')->default('PENDING');
            $table->text('screening_notes')->nullable();
            $table->foreignId('screened_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('screened_at')->nullable();
            $table->string('status')->default('COLLECTED');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['blood_group', 'screening_status']);
            $table->index('donation_date');
        });

        Schema::create('blood_units', function (Blueprint $table) {
            $table->id();
            $table->string('unit_number')->unique();
            $table->foreignId('donation_id')->nullable()->constrained('blood_donations')->nullOnDelete();
            $table->foreignId('donor_id')->nullable()->constrained('blood_donors')->nullOnDelete();
            $table->string('blood_group');
            $table->string('component_type')->default('WHOLE_BLOOD');
            $table->unsignedSmallInteger('volume_ml')->nullable();
            $table->date('collection_date');
            $table->date('expiry_date');
            $table->foreignId('storage_location_id')->nullable()->constrained('blood_storage_locations')->nullOnDelete();
            $table->string('screening_status')->default('PENDING');
            $table->string('crossmatch_status')->nullable();
            $table->string('status')->default('QUARANTINED');
            $table->unsignedBigInteger('reserved_for_request_id')->nullable()->index();
            $table->dateTime('issued_at')->nullable();
            $table->dateTime('discarded_at')->nullable();
            $table->foreignId('discarded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('discard_reason')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['blood_group', 'component_type', 'status']);
            $table->index('expiry_date');
            $table->index('screening_status');
        });

        Schema::create('blood_requests', function (Blueprint $table) {
            $table->id();
            $table->string('request_number')->unique();
            $table->foreignId('visit_id')->constrained()->cascadeOnDelete();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->foreignId('admission_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('emergency_case_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('department_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('requested_by')->constrained('users')->cascadeOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('requested_at');
            $table->dateTime('approved_at')->nullable();
            $table->dateTime('needed_at')->nullable();
            $table->string('blood_group');
            $table->string('component_type')->default('WHOLE_BLOOD');
            $table->unsignedSmallInteger('units_requested')->default(1);
            $table->unsignedSmallInteger('units_issued')->default(0);
            $table->string('priority')->default('ROUTINE');
            $table->string('status')->default('PENDING');
            $table->string('hb_level')->nullable();
            $table->string('diagnosis')->nullable();
            $table->text('indication')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('invoice_item_id')->nullable()->constrained('invoice_items')->nullOnDelete();
            $table->timestamps();

            $table->index(['blood_group', 'component_type', 'status']);
            $table->index(['visit_id', 'patient_id']);
            $table->index('needed_at');
        });

        Schema::create('blood_crossmatches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('blood_request_id')->constrained('blood_requests')->cascadeOnDelete();
            $table->foreignId('blood_unit_id')->constrained('blood_units')->cascadeOnDelete();
            $table->foreignId('visit_id')->constrained()->cascadeOnDelete();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->foreignId('performed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('performed_at')->nullable();
            $table->string('result')->default('PENDING');
            $table->string('method')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['blood_request_id', 'blood_unit_id']);
            $table->index('result');
        });

        Schema::create('blood_issues', function (Blueprint $table) {
            $table->id();
            $table->string('issue_number')->unique();
            $table->foreignId('blood_request_id')->constrained('blood_requests')->cascadeOnDelete();
            $table->foreignId('blood_unit_id')->constrained('blood_units')->cascadeOnDelete();
            $table->foreignId('visit_id')->constrained()->cascadeOnDelete();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->foreignId('admission_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('emergency_case_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('issued_by')->constrained('users')->cascadeOnDelete();
            $table->foreignId('received_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('received_by_name')->nullable();
            $table->dateTime('issued_at');
            $table->foreignId('transfused_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('transfused_at')->nullable();
            $table->string('transfusion_status')->default('ISSUED');
            $table->text('reaction_notes')->nullable();
            $table->text('notes')->nullable();
            $table->dateTime('returned_at')->nullable();
            $table->foreignId('returned_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('return_reason')->nullable();
            $table->timestamps();

            $table->unique('blood_unit_id');
            $table->index(['visit_id', 'patient_id']);
            $table->index('transfusion_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('blood_issues');
        Schema::dropIfExists('blood_crossmatches');
        Schema::dropIfExists('blood_units');
        Schema::dropIfExists('blood_requests');
        Schema::dropIfExists('blood_donations');
        Schema::dropIfExists('blood_storage_locations');
        Schema::dropIfExists('blood_donors');
    }
};
