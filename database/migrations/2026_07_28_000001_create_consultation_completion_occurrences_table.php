<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 14R.8 — durable ledger of genuine consultation completion occurrences.
 *
 * Closes risk P2. Before this, a completion was identified by
 * `route:{id}@{completed_at}` — second-precision — so a reopen and
 * recompletion inside the same second collapsed into one occurrence and the
 * second completion's clinical state was never captured.
 *
 * Why a dedicated table rather than an existing row:
 *   - `visit_consultation_route_logs` does record every transition inside the
 *     completion transaction, but it is a mutable, multi-purpose transition log
 *     (routed / paused / activated / completed / cancelled). Binding
 *     medico-legal snapshot identity to a mutable general log is exactly what
 *     the phase brief rules out.
 *   - `activity_log` is optional and may be asynchronous — also ruled out.
 *
 * Append-only by construction: no `updated_at`, no soft deletes. A row is
 * written once, inside the completion transaction, under a route row lock.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('consultation_completion_occurrences', function (Blueprint $table) {
            $table->id();

            $table->foreignId('consultation_route_id')
                ->constrained('visit_consultation_routes', indexName: 'cco_route_fk')
                ->cascadeOnDelete();

            $table->foreignId('visit_id')->nullable()
                ->constrained('visits', indexName: 'cco_visit_fk')->nullOnDelete();

            // Immutable, generated identity used as the snapshot idempotency
            // key. Never derived from a timestamp.
            $table->ulid('occurrence_uid')->unique('cco_uid_unique');

            // Monotonic per route: 1, 2, 3 … Allocated under a row lock.
            $table->unsignedInteger('occurrence_number');

            $table->foreignId('previous_occurrence_id')->nullable()
                ->constrained('consultation_completion_occurrences', indexName: 'cco_previous_fk')
                ->nullOnDelete();

            $table->string('from_status', 40)->nullable();
            $table->string('to_status', 40);

            // Human-readable completion time is PRESERVED, but is no longer the
            // identity.
            $table->timestamp('completed_at');

            $table->foreignId('completed_by')->nullable()
                ->constrained('users', indexName: 'cco_completed_by_fk')->nullOnDelete();

            $table->json('metadata')->nullable();

            // created_at only — append-only, by design.
            $table->timestamp('created_at')->nullable();

            $table->unique(['consultation_route_id', 'occurrence_number'], 'cco_route_number_unique');
            $table->index('completed_at', 'cco_completed_at_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('consultation_completion_occurrences');
    }
};
