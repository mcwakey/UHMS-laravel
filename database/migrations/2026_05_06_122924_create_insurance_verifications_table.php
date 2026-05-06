<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('insurance_verifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_insurance_id')->constrained('patient_insurances')->cascadeOnDelete();
            $table->foreignId('insurance_provider_id')->constrained('insurance_providers')->restrictOnDelete();
            $table->foreignId('visit_id')->nullable()->constrained('visits')->nullOnDelete();

            $table->string('driver', 40);                    // manual | code | api | ...
            $table->string('status', 30);                    // VerificationStatus value
            $table->string('reference_code', 80)->nullable();
            $table->string('membership_number', 80)->nullable(); // snapshot
            $table->string('member_name')->nullable();
            $table->date('expires_at')->nullable();

            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('verified_at')->nullable();

            $table->longText('payload')->nullable();             // raw driver response (sanitized)
            $table->text('message')->nullable();
            $table->timestamps();

            $table->index(['patient_insurance_id', 'status']);
            $table->index(['visit_id']);
            $table->index(['insurance_provider_id', 'verified_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('insurance_verifications');
    }
};
