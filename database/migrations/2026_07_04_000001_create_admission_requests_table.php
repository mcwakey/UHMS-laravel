<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('admission_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->foreignId('visit_id')->nullable()->constrained()->nullOnDelete();
            $table->string('source_type')->default('direct');
            $table->unsignedBigInteger('source_id')->nullable();
            $table->foreignId('requested_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('accepted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('rejected_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('cancelled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('requested_ward_id')->nullable()->constrained('wards')->nullOnDelete();
            $table->string('preferred_bed_type')->nullable();
            $table->foreignId('reserved_bed_id')->nullable()->constrained('beds')->nullOnDelete();
            $table->string('priority')->nullable();
            $table->text('provisional_diagnosis')->nullable();
            $table->text('clinical_summary')->nullable();
            $table->string('status')->default('requested');
            $table->timestamp('requested_at')->nullable();
            $table->timestamp('accepted_at')->nullable();
            $table->timestamp('rejected_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamp('converted_at')->nullable();
            $table->text('reason')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'requested_at']);
            $table->index(['source_type', 'source_id']);
            $table->index('priority');
        });

        Schema::table('admissions', function (Blueprint $table) {
            $table->foreignId('admission_request_id')
                ->nullable()
                ->after('id')
                ->constrained('admission_requests')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('admissions', function (Blueprint $table) {
            $table->dropConstrainedForeignId('admission_request_id');
        });

        Schema::dropIfExists('admission_requests');
    }
};
