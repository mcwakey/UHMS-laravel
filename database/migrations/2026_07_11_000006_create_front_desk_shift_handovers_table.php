<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Front Desk shift handover log (Phase 18E). Reception / security staff hand over
 * open operational issues at shift change. Snapshot counts come from the existing
 * Front Desk services — no sensitive free-text or clinical data is copied in.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('front_desk_shift_handovers', function (Blueprint $table) {
            $table->id();
            $table->date('shift_date');
            $table->string('shift_name')->nullable();
            $table->dateTime('handover_started_at');
            $table->dateTime('handover_completed_at')->nullable();
            $table->foreignId('outgoing_user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('incoming_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('department_id')->nullable()->constrained('departments')->nullOnDelete();
            // draft | submitted | accepted | cancelled
            $table->string('status')->default('draft')->index();

            $table->unsignedInteger('visitors_inside_count')->default(0);
            $table->unsignedInteger('pending_callbacks_count')->default(0);
            $table->unsignedInteger('pending_couriers_count')->default(0);
            $table->unsignedInteger('open_incidents_count')->default(0);
            $table->unsignedInteger('lost_found_unclaimed_count')->default(0);

            $table->text('summary_notes')->nullable();
            $table->json('open_items_snapshot')->nullable();

            $table->foreignId('submitted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('submitted_at')->nullable();
            $table->foreignId('accepted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('accepted_at')->nullable();
            $table->foreignId('cancelled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('cancelled_at')->nullable();

            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['shift_date', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('front_desk_shift_handovers');
    }
};
