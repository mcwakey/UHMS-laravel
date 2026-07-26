<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 14R.5 — Emergency ↔ Maternity bridge.
 *
 * Additive only, and structurally identical to `consultation_maternity_links`
 * (14R.2): explicit nullable target FKs, closed context-type set, full audit
 * columns, and the nullable `active_slot` trick that gives MySQL/MariaDB
 * "exactly one ACTIVE link per (source, context type), unlimited history"
 * without partial unique indexes.
 *
 * An Emergency case NEVER acquires maternity context implicitly — rows here are
 * written only by EmergencyMaternityLinkService from an explicit clinician
 * action.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('emergency_maternity_links', function (Blueprint $table) {
            $table->id();

            $table->foreignId('emergency_case_id')
                ->constrained('emergency_cases', indexName: 'eml_emergency_case_fk')
                ->cascadeOnDelete();

            $table->foreignId('pregnancy_profile_id')->nullable()
                ->constrained('pregnancy_profiles', indexName: 'eml_pregnancy_profile_fk')
                ->nullOnDelete();

            $table->foreignId('maternity_case_id')->nullable()
                ->constrained('maternity_cases', indexName: 'eml_maternity_case_fk')->nullOnDelete();
            $table->foreignId('antenatal_visit_id')->nullable()
                ->constrained('antenatal_visits', indexName: 'eml_antenatal_visit_fk')->nullOnDelete();
            $table->foreignId('labor_episode_id')->nullable()
                ->constrained('labor_episodes', indexName: 'eml_labor_episode_fk')->nullOnDelete();
            $table->foreignId('delivery_record_id')->nullable()
                ->constrained('delivery_records', indexName: 'eml_delivery_record_fk')->nullOnDelete();
            $table->foreignId('newborn_record_id')->nullable()
                ->constrained('newborn_records', indexName: 'eml_newborn_record_fk')->nullOnDelete();
            $table->foreignId('postnatal_case_id')->nullable()
                ->constrained('postnatal_cases', indexName: 'eml_postnatal_case_fk')->nullOnDelete();

            $table->string('context_type', 32);
            $table->string('link_role', 32);

            $table->foreignId('linked_by')->nullable()
                ->constrained('users', indexName: 'eml_linked_by_fk')->nullOnDelete();
            $table->timestamp('linked_at');
            $table->foreignId('unlinked_by')->nullable()
                ->constrained('users', indexName: 'eml_unlinked_by_fk')->nullOnDelete();
            $table->timestamp('unlinked_at')->nullable();
            $table->text('reason')->nullable();
            $table->json('metadata')->nullable();

            // 1 = active, NULL = historical.
            $table->unsignedTinyInteger('active_slot')->nullable();

            $table->timestamps();

            $table->unique(
                ['emergency_case_id', 'context_type', 'active_slot'],
                'eml_active_context_unique'
            );

            $table->index(['emergency_case_id', 'context_type'], 'eml_case_context_idx');
            $table->index('linked_at', 'eml_linked_at_idx');
            $table->index('unlinked_at', 'eml_unlinked_at_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('emergency_maternity_links');
    }
};
