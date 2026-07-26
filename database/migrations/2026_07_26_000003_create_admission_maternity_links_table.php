<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 14R.5 — Admission ↔ Maternity bridge.
 *
 * This table — not `pregnancy_profiles.admission_id` — is the durable source of
 * "which admission is this pregnancy currently being cared for under".
 * A single overwritable column on the profile cannot represent a patient's
 * second or third admission without destroying the first, so the profile column
 * is left exactly as it is and never force-updated by this phase.
 *
 * Same active-slot uniqueness as the Consultation and Emergency bridges.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('admission_maternity_links', function (Blueprint $table) {
            $table->id();

            $table->foreignId('admission_id')
                ->constrained('admissions', indexName: 'aml_admission_fk')
                ->cascadeOnDelete();

            // Where this context came from, when it was propagated rather than
            // linked by hand. Never used to infer context — audit only.
            $table->foreignId('admission_request_id')->nullable()
                ->constrained('admission_requests', indexName: 'aml_admission_request_fk')
                ->nullOnDelete();

            $table->foreignId('pregnancy_profile_id')->nullable()
                ->constrained('pregnancy_profiles', indexName: 'aml_pregnancy_profile_fk')
                ->nullOnDelete();

            $table->foreignId('maternity_case_id')->nullable()
                ->constrained('maternity_cases', indexName: 'aml_maternity_case_fk')->nullOnDelete();
            $table->foreignId('antenatal_visit_id')->nullable()
                ->constrained('antenatal_visits', indexName: 'aml_antenatal_visit_fk')->nullOnDelete();
            $table->foreignId('labor_episode_id')->nullable()
                ->constrained('labor_episodes', indexName: 'aml_labor_episode_fk')->nullOnDelete();
            $table->foreignId('delivery_record_id')->nullable()
                ->constrained('delivery_records', indexName: 'aml_delivery_record_fk')->nullOnDelete();
            $table->foreignId('newborn_record_id')->nullable()
                ->constrained('newborn_records', indexName: 'aml_newborn_record_fk')->nullOnDelete();
            $table->foreignId('postnatal_case_id')->nullable()
                ->constrained('postnatal_cases', indexName: 'aml_postnatal_case_fk')->nullOnDelete();

            $table->string('context_type', 32);
            $table->string('link_role', 32);

            $table->foreignId('linked_by')->nullable()
                ->constrained('users', indexName: 'aml_linked_by_fk')->nullOnDelete();
            $table->timestamp('linked_at');
            $table->foreignId('unlinked_by')->nullable()
                ->constrained('users', indexName: 'aml_unlinked_by_fk')->nullOnDelete();
            $table->timestamp('unlinked_at')->nullable();
            $table->text('reason')->nullable();
            $table->json('metadata')->nullable();

            $table->unsignedTinyInteger('active_slot')->nullable();

            $table->timestamps();

            $table->unique(
                ['admission_id', 'context_type', 'active_slot'],
                'aml_active_context_unique'
            );

            $table->index(['admission_id', 'context_type'], 'aml_admission_context_idx');
            $table->index('linked_at', 'aml_linked_at_idx');
            $table->index('unlinked_at', 'aml_unlinked_at_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admission_maternity_links');
    }
};
