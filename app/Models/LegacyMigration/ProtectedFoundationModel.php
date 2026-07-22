<?php

namespace App\Models\LegacyMigration;

use Illuminate\Database\Eloquent\Model;
use App\Services\LegacyMigration\Foundation\Security\Phase3ProtectedStoreModelGuard;

abstract class ProtectedFoundationModel extends Model
{
    protected $guarded = ['id'];

    /**
     * Protected tokens and encrypted evidence never belong in ordinary model
     * serialization. Storage services expose purpose-built aggregate views.
     *
     * @var list<string>
     */
    protected $hidden = [
        'run_token',
        'bundle_token',
        'collision_snapshot_token',
        'remediation_token',
        'supersedes_token',
        'outcome_coordinate_token',
        'event_token',
        'protected_source_token',
        'protected_target_token',
        'patient_root_token',
        'subchain_token',
        'root_token',
        'cohort_token',
        'snapshot_token',
        'coordinate_token',
        'active_coordinate_token',
        'active_source_guard_key',
        'chain_coordinate_token',
        'collision_coordinate_token',
        'dependency_chain_token',
        'idempotency_token',
        'intent_token',
        'compensation_token',
        'protected_number_token',
        'numbering_coordinate_token',
        'patient_number_result_token',
        'existing_target_evidence_token',
        'run_provenance_token',
        'evidence_issuer_token',
        'reviewer_token',
        'manual_review_owner_token',
        'release_approval_token',
        'approval_token',
        'actor_token',
        'created_by_token',
        'updated_by_token',
        'primary_guard_token',
        'primary_root_guard_key',
        'encrypted_manifest',
        'encrypted_metadata',
        'encrypted_evidence',
        'encrypted_values',
        'encrypted_evidence_metadata',
        'encrypted_mapping_payload',
        'encrypted_authoritative_rule_ids',
        'encrypted_raw_payload',
        'encrypted_field_dispositions',
        'encrypted_outcome_metadata',
        'encrypted_release_conditions',
        'encrypted_dependency_edges',
        'encrypted_population_definition',
        'encrypted_expected_equation',
        'encrypted_measured_values',
        'encrypted_exception_refs',
        'encrypted_event_payload',
        'encrypted_outcome_payload',
        'encrypted_write_set_refs',
        'encrypted_durable_fact_refs',
        'encrypted_action_payload',
        'encrypted_number_payload',
        'encrypted_explanation',
        'integrity_checksum',
        'manifest_checksum',
        'mapping_checksum',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'mandatory' => 'boolean',
            'measurement_complete' => 'boolean',
            'has_unresolved_conflict' => 'boolean',
            'is_primary' => 'boolean',
            'operator_review_required' => 'boolean',
            'started_at' => 'immutable_datetime',
            'completed_at' => 'immutable_datetime',
            'captured_at' => 'immutable_datetime',
            'expires_at' => 'immutable_datetime',
            'valid_from' => 'immutable_datetime',
            'valid_until' => 'immutable_datetime',
            'revoked_at' => 'immutable_datetime',
            'manual_review_due_at' => 'immutable_datetime',
            'occurred_at' => 'immutable_datetime',
            'executed_at' => 'immutable_datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(static fn (Model $model) => Phase3ProtectedStoreModelGuard::assertWriteAllowed($model));
        static::updating(static fn (Model $model) => Phase3ProtectedStoreModelGuard::assertWriteAllowed($model));
        static::deleting(static fn () => Phase3ProtectedStoreModelGuard::denyDelete());
    }
}
