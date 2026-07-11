<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Front Desk / Security Desk incident log (Phase 18E). Operational, NON-clinical
 * incident tracking at reception (aggressive visitor, unauthorized access, queue
 * dispute, etc.). This is NOT a clinical incident report and never carries
 * diagnosis, treatment or medical details.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('front_desk_incident_logs', function (Blueprint $table) {
            $table->id();
            $table->string('incident_number')->nullable()->unique();
            // App\Enums\FrontDesk\FrontDeskIncidentType
            $table->string('incident_type')->default('other')->index();
            // low | medium | high | critical
            $table->string('severity')->default('low')->index();
            // open | in_progress | escalated | resolved | cancelled
            $table->string('status')->default('open')->index();
            $table->dateTime('reported_at');
            $table->string('reported_by_name')->nullable();
            $table->string('reported_by_phone')->nullable();
            $table->string('location')->nullable();
            $table->foreignId('department_id')->nullable()->constrained('departments')->nullOnDelete();

            $table->foreignId('related_visitor_log_id')->nullable()->constrained('front_desk_visitor_logs')->nullOnDelete();
            $table->foreignId('related_call_log_id')->nullable()->constrained('front_desk_call_logs')->nullOnDelete();
            $table->foreignId('related_courier_log_id')->nullable()->constrained('front_desk_courier_logs')->nullOnDelete();

            $table->foreignId('assigned_to_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('escalated_to_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('escalated_at')->nullable();
            $table->foreignId('resolved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('resolved_at')->nullable();

            $table->text('description');
            $table->text('action_taken')->nullable();
            $table->text('resolution_note')->nullable();

            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index('reported_at');
            $table->index(['status', 'severity']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('front_desk_incident_logs');
    }
};
