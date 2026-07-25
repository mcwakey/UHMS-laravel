<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 14R.2 — Consultation ↔ Maternity bridge.
 *
 * Additive only. Links a consultation encounter (`visit_consultation_routes`)
 * to a longitudinal maternity record without duplicating any clinical field.
 *
 * Active-link uniqueness on MySQL/MariaDB
 * ---------------------------------------
 * MySQL does not support partial unique indexes (`WHERE unlinked_at IS NULL`),
 * but it *does* permit unlimited NULLs inside a unique index. So the active
 * link is marked with `active_slot = 1` and historical rows carry
 * `active_slot = NULL`. The unique index on
 * (consultation_route_id, context_type, active_slot) therefore allows:
 *
 *   - exactly one ACTIVE link per consultation/context type
 *   - unlimited historical (unlinked) rows for the same pair
 *
 * A plain unique index on an `is_active` boolean would break the second
 * property, which is why `active_slot` is nullable rather than boolean.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('consultation_maternity_links', function (Blueprint $table) {
            $table->id();

            // Consultation encounter key. Confirmed in the 14R.1 audit as the
            // same key ConsultationSpecialtyEntry.consultation_id points at.
            $table->foreignId('consultation_route_id')
                ->constrained('visit_consultation_routes', indexName: 'cml_consultation_route_fk')
                ->cascadeOnDelete();

            // Longitudinal root, derived from the target where available.
            $table->foreignId('pregnancy_profile_id')->nullable()
                ->constrained('pregnancy_profiles', indexName: 'cml_pregnancy_profile_fk')
                ->nullOnDelete();

            // Explicit nullable target keys (approved over a polymorphic id).
            $table->foreignId('maternity_case_id')->nullable()
                ->constrained('maternity_cases', indexName: 'cml_maternity_case_fk')->nullOnDelete();
            $table->foreignId('antenatal_visit_id')->nullable()
                ->constrained('antenatal_visits', indexName: 'cml_antenatal_visit_fk')->nullOnDelete();
            $table->foreignId('labor_episode_id')->nullable()
                ->constrained('labor_episodes', indexName: 'cml_labor_episode_fk')->nullOnDelete();
            $table->foreignId('delivery_record_id')->nullable()
                ->constrained('delivery_records', indexName: 'cml_delivery_record_fk')->nullOnDelete();
            $table->foreignId('newborn_record_id')->nullable()
                ->constrained('newborn_records', indexName: 'cml_newborn_record_fk')->nullOnDelete();
            $table->foreignId('postnatal_case_id')->nullable()
                ->constrained('postnatal_cases', indexName: 'cml_postnatal_case_fk')->nullOnDelete();

            $table->string('context_type', 32);
            $table->string('link_role', 32);

            $table->foreignId('linked_by')->nullable()
                ->constrained('users', indexName: 'cml_linked_by_fk')->nullOnDelete();
            $table->timestamp('linked_at');
            $table->foreignId('unlinked_by')->nullable()
                ->constrained('users', indexName: 'cml_unlinked_by_fk')->nullOnDelete();
            $table->timestamp('unlinked_at')->nullable();
            $table->text('reason')->nullable();
            $table->json('metadata')->nullable();

            // 1 = active, NULL = historical. See class docblock.
            $table->unsignedTinyInteger('active_slot')->nullable();

            $table->timestamps();

            $table->unique(
                ['consultation_route_id', 'context_type', 'active_slot'],
                'cml_active_context_unique'
            );

            $table->index(['consultation_route_id', 'context_type'], 'cml_consultation_context_idx');
            $table->index('linked_at', 'cml_linked_at_idx');
            $table->index('unlinked_at', 'cml_unlinked_at_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('consultation_maternity_links');
    }
};
