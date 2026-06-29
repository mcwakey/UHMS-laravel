<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 9.8 — lightweight AGGREGATE operational analytics. One row = the counts for a
 * (date, granularity, from-type → to-type, cause, sla_status) bucket. Stores totals
 * (not averages) so the query service can re-aggregate correctly. NO patient names,
 * visit numbers or journey timelines — aggregate only.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('journey_flow_snapshots', function (Blueprint $table) {
            $table->id();
            $table->date('snapshot_date')->index();
            $table->unsignedTinyInteger('snapshot_hour')->nullable();
            $table->string('granularity', 10)->default('day')->index();

            $table->unsignedBigInteger('from_department_id')->nullable()->index();
            $table->string('from_department_type', 50)->nullable();
            $table->unsignedBigInteger('to_department_id')->nullable()->index();
            $table->string('to_department_type', 50)->nullable();

            $table->string('cause', 50)->index();
            $table->string('stage', 50)->nullable();
            $table->string('sla_status', 20)->index();
            $table->string('assignment_status', 20)->nullable()->index();
            $table->string('escalation_level', 20)->nullable()->index();

            // Aggregate counters.
            $table->unsignedInteger('handoff_count')->default(0);
            $table->unsignedInteger('breached_count')->default(0);
            $table->unsignedInteger('critical_breach_count')->default(0);
            $table->unsignedInteger('unassigned_count')->default(0);
            $table->unsignedInteger('assigned_count')->default(0);
            $table->unsignedInteger('acknowledged_count')->default(0);
            $table->unsignedInteger('resolved_count')->default(0);
            $table->unsignedBigInteger('total_elapsed_minutes')->default(0);
            $table->unsignedBigInteger('total_time_to_acknowledge_minutes')->nullable();
            $table->unsignedBigInteger('total_time_to_resolve_minutes')->nullable();

            $table->timestamps();

            // One bucket per (date, grain, from-type, to-type, cause, sla). Key columns
            // are always populated so the upsert match is reliable.
            $table->unique(
                ['snapshot_date', 'granularity', 'from_department_type', 'to_department_type', 'cause', 'sla_status'],
                'jfs_bucket_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('journey_flow_snapshots');
    }
};
