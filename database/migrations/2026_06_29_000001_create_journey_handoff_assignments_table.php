<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 9.5 — operational coordination state for cross-department handoffs.
 *
 * This table stores ONLY ownership/escalation metadata. The current delay, cause,
 * SLA and handoff remain DERIVED from existing operational records (Phase 9.1–9.4)
 * and are never duplicated here. One row tracks one unresolved handoff identity
 * (visit + cause + from/to department).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('journey_handoff_assignments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('visit_id')->index();
            $table->string('cause', 50)->index();
            $table->unsignedBigInteger('from_department_id')->nullable()->index();
            $table->unsignedBigInteger('to_department_id')->nullable()->index();
            $table->string('to_department_type', 50)->nullable();
            $table->unsignedBigInteger('assigned_to_user_id')->nullable()->index();
            $table->unsignedBigInteger('assigned_by_user_id')->nullable();
            $table->string('status', 20)->default('unassigned')->index();
            $table->string('escalation_level', 20)->default('none')->index();
            $table->timestamp('assigned_at')->nullable();
            $table->timestamp('acknowledged_at')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamp('last_escalated_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            // Identity lookup: the same unresolved handoff reuses one row.
            $table->index(['visit_id', 'cause', 'to_department_id'], 'jha_identity_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('journey_handoff_assignments');
    }
};
