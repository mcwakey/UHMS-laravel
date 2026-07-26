<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 14R.5 — Admission Request ↔ Maternity clinical context.
 *
 * DELIBERATELY separate from `admission_requests.source_type/source_id`.
 *
 * The operational source must stay truthful: a request raised by Emergency is
 * `emergency`/EmergencyCase-id, one raised from a consultation is
 * `consultation`/VisitConsultationRoute-id. Overloading those columns to also
 * carry pregnancy context would make the origin unreadable — and the legacy
 * `maternity` source is already ambiguous (source_id may be an AntenatalVisit
 * OR a LaborEpisode id, per AntenatalVisitService/LaborEpisodeService).
 *
 * Clinical maternity context therefore lives here, additively. Requests without
 * a row remain completely valid.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('admission_request_maternity_links', function (Blueprint $table) {
            $table->id();

            $table->foreignId('admission_request_id')
                ->constrained('admission_requests', indexName: 'arml_admission_request_fk')
                ->cascadeOnDelete();

            $table->foreignId('pregnancy_profile_id')->nullable()
                ->constrained('pregnancy_profiles', indexName: 'arml_pregnancy_profile_fk')
                ->nullOnDelete();

            $table->foreignId('maternity_case_id')->nullable()
                ->constrained('maternity_cases', indexName: 'arml_maternity_case_fk')->nullOnDelete();
            $table->foreignId('antenatal_visit_id')->nullable()
                ->constrained('antenatal_visits', indexName: 'arml_antenatal_visit_fk')->nullOnDelete();
            $table->foreignId('labor_episode_id')->nullable()
                ->constrained('labor_episodes', indexName: 'arml_labor_episode_fk')->nullOnDelete();
            $table->foreignId('delivery_record_id')->nullable()
                ->constrained('delivery_records', indexName: 'arml_delivery_record_fk')->nullOnDelete();
            $table->foreignId('newborn_record_id')->nullable()
                ->constrained('newborn_records', indexName: 'arml_newborn_record_fk')->nullOnDelete();
            $table->foreignId('postnatal_case_id')->nullable()
                ->constrained('postnatal_cases', indexName: 'arml_postnatal_case_fk')->nullOnDelete();

            $table->string('context_type', 32);
            $table->string('link_role', 32);

            $table->foreignId('linked_by')->nullable()
                ->constrained('users', indexName: 'arml_linked_by_fk')->nullOnDelete();
            $table->timestamp('linked_at');
            $table->foreignId('unlinked_by')->nullable()
                ->constrained('users', indexName: 'arml_unlinked_by_fk')->nullOnDelete();
            $table->timestamp('unlinked_at')->nullable();
            $table->text('reason')->nullable();
            $table->json('metadata')->nullable();

            $table->unsignedTinyInteger('active_slot')->nullable();

            $table->timestamps();

            $table->unique(
                ['admission_request_id', 'context_type', 'active_slot'],
                'arml_active_context_unique'
            );

            $table->index(['admission_request_id', 'context_type'], 'arml_request_context_idx');
            $table->index('linked_at', 'arml_linked_at_idx');
            $table->index('unlinked_at', 'arml_unlinked_at_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admission_request_maternity_links');
    }
};
