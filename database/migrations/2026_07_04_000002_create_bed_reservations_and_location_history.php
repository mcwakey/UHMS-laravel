<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('beds', function (Blueprint $table) {
            $table->timestamp('reserved_until')->nullable()->after('status');
            $table->string('status_reason')->nullable()->after('reserved_until');
            $table->foreignId('status_changed_by')->nullable()->after('status_reason')->constrained('users')->nullOnDelete();
            $table->timestamp('status_changed_at')->nullable()->after('status_changed_by');
        });

        Schema::create('bed_reservations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('admission_request_id')->nullable()->constrained('admission_requests')->nullOnDelete();
            $table->foreignId('admission_id')->nullable()->constrained('admissions')->nullOnDelete();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->foreignId('visit_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('bed_id')->constrained()->cascadeOnDelete();
            $table->string('status')->default('active');
            $table->foreignId('reserved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('released_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reserved_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('released_at')->nullable();
            $table->text('reason')->nullable();
            $table->timestamps();

            $table->index(['bed_id', 'status']);
            $table->index(['admission_request_id', 'status']);
            $table->index(['admission_id', 'status']);
            $table->index('expires_at');
        });

        Schema::create('admission_location_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('admission_id')->constrained()->cascadeOnDelete();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->foreignId('visit_id')->nullable()->constrained()->nullOnDelete();
            $table->string('event_type');
            $table->foreignId('from_ward_id')->nullable()->constrained('wards')->nullOnDelete();
            $table->foreignId('from_bed_id')->nullable()->constrained('beds')->nullOnDelete();
            $table->foreignId('to_ward_id')->nullable()->constrained('wards')->nullOnDelete();
            $table->foreignId('to_bed_id')->nullable()->constrained('beds')->nullOnDelete();
            $table->foreignId('moved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('moved_at')->nullable();
            $table->text('reason')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['admission_id', 'moved_at']);
            $table->index('event_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admission_location_histories');
        Schema::dropIfExists('bed_reservations');

        Schema::table('beds', function (Blueprint $table) {
            $table->dropConstrainedForeignId('status_changed_by');
            $table->dropColumn(['reserved_until', 'status_reason', 'status_changed_at']);
        });
    }
};
