<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Front Desk Operations (Phase 18A) — visitor logs.
 *
 * People entering the facility: relatives visiting admitted patients, and
 * people visiting departments / admin offices. This is deliberately NOT the
 * clinical patient "visits" table — patient links here are optional and only
 * safe identifiers are ever surfaced (no clinical data).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('front_desk_visitor_logs', function (Blueprint $table) {
            $table->id();

            // patient | facility | other  (App\Enums\FrontDesk\VisitorContext)
            $table->string('visitor_context')->default('facility')->index();
            $table->string('visitor_name');
            $table->string('visitor_phone')->nullable();
            $table->string('id_type')->nullable();
            $table->string('id_number')->nullable();
            $table->string('organization')->nullable();

            // Optional links — front desk records must work with no patient link.
            $table->foreignId('patient_id')->nullable()->constrained('patients')->nullOnDelete();
            $table->foreignId('visit_id')->nullable()->constrained('visits')->nullOnDelete();
            $table->foreignId('admission_id')->nullable()->constrained('admissions')->nullOnDelete();
            $table->foreignId('ward_id')->nullable()->constrained('wards')->nullOnDelete();
            $table->foreignId('bed_id')->nullable()->constrained('beds')->nullOnDelete();
            $table->foreignId('department_id')->nullable()->constrained('departments')->nullOnDelete();

            $table->string('relationship_to_patient')->nullable();
            $table->string('person_to_see')->nullable();
            $table->text('purpose')->nullable();
            $table->string('badge_number')->nullable();
            $table->string('vehicle_number')->nullable();

            $table->dateTime('time_in');
            $table->dateTime('time_out')->nullable();

            // checked_in | checked_out | denied | cancelled (VisitorStatus)
            $table->string('status')->default('checked_in')->index();

            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('checked_in_by')->constrained('users')->cascadeOnDelete();
            $table->foreignId('checked_out_by')->nullable()->constrained('users')->nullOnDelete();

            $table->text('notes')->nullable();
            $table->json('metadata')->nullable();

            $table->timestamps();

            $table->index('time_in');
            $table->index(['status', 'time_out']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('front_desk_visitor_logs');
    }
};
