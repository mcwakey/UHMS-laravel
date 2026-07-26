<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 14R.6 — immutable, versioned maternity snapshot captured at
 * consultation completion.
 *
 * Append-only by design:
 *   - there is deliberately NO `updated_at` and NO soft-delete column, so the
 *     schema itself says "rows are never edited or retired";
 *   - corrections happen by reopening the consultation, fixing the maternity
 *     record and recompleting, which writes the NEXT version;
 *   - `previous_snapshot_id` chains the versions for history navigation.
 *
 * `completion_reference` is the completion OCCURRENCE token. The audit
 * established that consultation completion is owned by
 * ConsultationRouteService::completeRoute(), which early-returns when the route
 * is already completed and stamps a fresh `completed_at` on each recompletion.
 * The reference is therefore derived from the route id plus that timestamp — an
 * existing, reliable identity rather than an invented one — and its uniqueness
 * index is the final guard against a concurrent double-capture.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('consultation_maternity_snapshots', function (Blueprint $table) {
            $table->id();

            $table->foreignId('consultation_route_id')
                ->constrained('visit_consultation_routes', indexName: 'cms_consultation_route_fk')
                ->cascadeOnDelete();

            $table->foreignId('pregnancy_profile_id')->nullable()
                ->constrained('pregnancy_profiles', indexName: 'cms_pregnancy_profile_fk')
                ->nullOnDelete();

            // Self-reference: the version this one supersedes.
            $table->foreignId('previous_snapshot_id')->nullable()
                ->constrained('consultation_maternity_snapshots', indexName: 'cms_previous_snapshot_fk')
                ->nullOnDelete();

            $table->unsignedInteger('snapshot_version');
            $table->string('schema_version', 32);
            $table->string('context_status', 32);
            $table->string('resolution_source', 32)->nullable();
            $table->string('completion_reference', 191)->nullable();

            $table->json('source_record_ids');
            $table->json('payload');
            $table->char('payload_hash', 64);

            $table->foreignId('captured_by')->nullable()
                ->constrained('users', indexName: 'cms_captured_by_fk')->nullOnDelete();
            $table->timestamp('captured_at');
            $table->json('metadata')->nullable();

            // created_at only — no updated_at, by design.
            $table->timestamp('created_at')->nullable();

            $table->unique(['consultation_route_id', 'snapshot_version'], 'cms_route_version_unique');
            $table->unique(['consultation_route_id', 'completion_reference'], 'cms_route_completion_unique');

            $table->index('pregnancy_profile_id', 'cms_pregnancy_profile_idx');
            $table->index('captured_at', 'cms_captured_at_idx');
            $table->index('previous_snapshot_id', 'cms_previous_snapshot_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('consultation_maternity_snapshots');
    }
};
