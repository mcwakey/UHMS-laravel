<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('patients', function (Blueprint $table) {
            $table->id();
            $table->string('patient_number')->unique();

            // Personal Information
            $table->string('first_name');
            $table->string('last_name');
            $table->string('other_names')->nullable();
            $table->date('date_of_birth');
            $table->string('gender');
            $table->string('blood_group')->nullable();
            $table->string('marital_status')->nullable();

            // Contact
            $table->string('phone');
            $table->string('phone_secondary')->nullable();
            $table->string('email')->nullable();

            // Ghana-Specific Identification
            $table->string('ghana_card_number')->nullable()->unique();
            $table->string('nhis_number')->nullable();
            $table->date('nhis_expiry_date')->nullable();

            // Demographics
            $table->string('occupation')->nullable();
            $table->text('address')->nullable();
            $table->string('city')->nullable();
            $table->string('region')->nullable();
            $table->string('digital_address')->nullable(); // Ghana Post GPS

            // Emergency Contact
            $table->string('emergency_contact_name')->nullable();
            $table->string('emergency_contact_phone')->nullable();
            $table->string('emergency_contact_relationship')->nullable();

            // Medical
            $table->string('avatar')->nullable();
            $table->text('allergies')->nullable();
            $table->text('chronic_conditions')->nullable();

            // Status & Meta
            $table->string('status')->default('active');
            $table->foreignId('registered_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index('phone');
            $table->index('nhis_number');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('patients');
    }
};
