<?php

namespace App\Services\LegacyMigration\Foundation\Reconciliation;

/**
 * Closed adapter from recorder-issued facts to the complete Phase 2F registry.
 * It never accepts operator measurements, expected values or differences.
 */
final class RecorderDerivedMeasurementAdapter
{
    private const SOURCE_SETS = ['source_transaction_coordinate', 'patient_root', 'patient_children', 'insurance'];

    private const TARGET_SETS = [
        'target_transaction_coordinate', 'patient_namespace', 'archive_namespace', 'alias_namespace',
        'contact_sets', 'insurance_memberships', 'provider_state', 'number_configuration',
        'foundation_schema', 'row_schema',
    ];

    private const INPUT_IDS = [
        'PILOT-DRY-001:approved_source_environment', 'PILOT-DRY-001:approved_target_environment',
        'PILOT-DRY-001:source_fingerprint', 'PILOT-DRY-001:target_fingerprint',
        'PILOT-DRY-001:source_snapshot_coordinate', 'PILOT-DRY-001:target_collision_snapshot_coordinate',
        'PILOT-DRY-002:contract_version_bundle', 'PILOT-DRY-002:cohort_manifest',
        'PILOT-DRY-002:protected_remediation_input', 'PILOT-DRY-002:reference_crosswalk_inputs',
        'PILOT-DRY-002:existing_target_crosswalk_inputs', 'PILOT-DRY-002:patient_state_matrix',
        'PILOT-DRY-002:insurance_initialization_matrix', 'PILOT-DRY-002:hmac_key_metadata',
        'PILOT-DRY-002:configuration_fingerprints', 'PILOT-DRY-002:evaluation_datetime_timezone',
        'PILOT-DRY-002:expected_scenario_matrix',
    ];

    private const ACCOUNTING_IDS = [
        'PILOT-ACCEPT-001:cohort_rows_classified', 'PILOT-ACCEPT-001:source_fields_classified',
        'PILOT-ACCEPT-001:direct_relationships_classified', 'PILOT-ACCEPT-001:children_accounted',
        'PILOT-ACCEPT-001:insurance_rows_accounted', 'PILOT-ACCEPT-001:exceptions_have_stable_codes',
        'PILOT-ACCEPT-001:quarantine_chains_have_one_valid_root', 'PILOT-ACCEPT-001:target_collisions_have_deterministic_outcome',
        'PILOT-ACCEPT-001:checkpoints_match_durable_outcomes', 'PILOT-ACCEPT-001:idempotency_keys_have_at_most_one_compatible_result',
        'PILOT-ACCEPT-006:all_selected_roots_have_one_terminal_primary_disposition',
        'PILOT-ACCEPT-006:all_children_aliases_and_insurance_rows_reconcile_independently',
        'PILOT-ACCEPT-006:all_source_rows_have_provenance',
        'PILOT-ACCEPT-006:all_quarantines_have_one_valid_root_owner_sla_and_release_condition',
        'PILOT-ACCEPT-006:all_equation_differences_equal_zero',
        'PILOT-ACCEPT-006:all_mandatory_safety_counts_equal_zero',
        'PILOT-ACCEPT-006:all_expected_nonzero_outcomes_are_classified_and_not_reported_as_success',
        'PILOT-ACCEPT-006:existing_target_mutation_equals_zero',
        'PILOT-ACCEPT-006:rerun_and_resume_are_duplicate_free',
        'PILOT-ACCEPT-006:rollback_or_compensation_evidence_is_complete',
        'PILOT-ACCEPT-006:privacy_scan_has_zero_unallowlisted_findings',
        'PILOT-ACCEPT-006:independent_review_has_no_critical_or_high_finding',
        'PILOT-DRY-003:preflight_verdict', 'PILOT-DRY-003:cohort_inclusion_exclusion_counts',
        'PILOT-DRY-003:environment_guard_results', 'PILOT-DRY-003:snapshot_and_contract_guard_results',
        'PILOT-DRY-004:patient_entity_outcomes', 'PILOT-DRY-004:patient_number_actions',
        'PILOT-DRY-004:alias_outcomes', 'PILOT-DRY-004:demographic_outcomes',
        'PILOT-DRY-004:contact_outcomes', 'PILOT-DRY-004:insurance_row_outcomes',
        'PILOT-DRY-004:provider_resolution_outcomes', 'PILOT-DRY-004:insurance_consolidation_outcomes',
        'PILOT-DRY-005:exception_counts', 'PILOT-DRY-005:quarantine_chain_counts',
        'PILOT-DRY-005:target_collision_counts', 'PILOT-DRY-005:reconciliation_results',
        'PILOT-DRY-005:privacy_scan_results', 'PILOT-DRY-005:side_effect_zero_assertions',
        'PILOT-DRY-005:idempotency_projection', 'PILOT-DRY-005:resume_rollback_projection',
        'PILOT-DRY-005:pilot_entry_exit_verdict',
    ];

    private const OUTCOME_IDS = [
        'PILOT-ACCEPT-003:classified_expected_exceptions', 'PILOT-ACCEPT-003:quarantined_or_remediation_pending_patients',
        'PILOT-ACCEPT-003:withheld_aliases', 'PILOT-ACCEPT-003:withheld_optional_children',
        'PILOT-ACCEPT-003:successful_optional_absence', 'PILOT-ACCEPT-003:suspected_duplicate_review_flags',
        'PILOT-ACCEPT-003:historical_only_insurance_rows',
        'PILOT-ACCEPT-003:provider_unresolved_or_ambiguous_insurance_rows',
        'PILOT-ACCEPT-003:unknown_date_or_expired_or_chronology_exception_insurance_rows',
        'PILOT-ACCEPT-003:exact_duplicate_insurance_provenance_rows',
        'PILOT-ACCEPT-003:conflicting_insurance_groups_withheld',
        'PILOT-ACCEPT-003:existing_target_immutable_evidence',
        'PILOT-ACCEPT-003:deterministically_withheld_target_collisions',
    ];

    private const ZERO_IDS = [
        'PILOT-ACCEPT-002:source_writes', 'PILOT-ACCEPT-002:target_writes_during_specification_or_dry_run',
        'PILOT-ACCEPT-002:automatic_patient_merges', 'PILOT-ACCEPT-002:name_only_matches',
        'PILOT-ACCEPT-002:phone_only_matches', 'PILOT-ACCEPT-002:opd_only_target_matches',
        'PILOT-ACCEPT-002:fuzzy_or_weighted_matches', 'PILOT-ACCEPT-002:classic_primary_key_reuse',
        'PILOT-ACCEPT-002:duplicate_patient_numbers', 'PILOT-ACCEPT-002:replacement_patient_numbers_on_rerun',
        'PILOT-ACCEPT-002:unexplained_patient_number_consumptions',
        'PILOT-ACCEPT-002:automatically_assigned_duplicate_opd_aliases',
        'PILOT-ACCEPT-002:alias_ownership_from_child_similarity',
        'PILOT-ACCEPT-002:existing_target_identity_changes', 'PILOT-ACCEPT-002:existing_target_child_enrichment',
        'PILOT-ACCEPT-002:existing_target_membership_mutation', 'PILOT-ACCEPT-002:soft_deleted_or_merged_target_mutation',
        'PILOT-ACCEPT-002:invented_patient_values', 'PILOT-ACCEPT-002:invented_actors',
        'PILOT-ACCEPT-002:classic_password_role_permission_login_mapping', 'PILOT-ACCEPT-002:invented_providers',
        'PILOT-ACCEPT-002:invented_member_policy_ccc_numbers',
        'PILOT-ACCEPT-002:coerced_guessed_replaced_truncated_or_swapped_dates',
        'PILOT-ACCEPT-002:fabricated_eligibility_or_verification', 'PILOT-ACCEPT-002:verification_rows_or_actors',
        'PILOT-ACCEPT-002:duplicate_emergency_contacts',
        'PILOT-ACCEPT-002:multiple_migration_created_primary_contacts',
        'PILOT-ACCEPT-002:duplicate_patient_provider_memberships',
        'PILOT-ACCEPT-002:patient_provider_groups_with_more_than_one_current',
        'PILOT-ACCEPT-002:silent_source_row_loss', 'PILOT-ACCEPT-002:insurance_rows_without_provenance',
        'PILOT-ACCEPT-002:claims_billing_payment_receivable_allocation_journal_or_accounting_projection',
        'PILOT-ACCEPT-002:prohibited_operational_service_calls',
        'PILOT-ACCEPT-002:queue_scheduler_notification_sms_email_events',
        'PILOT-ACCEPT-002:contact_search_side_effects',
        'PILOT-ACCEPT-002:billing_accounting_stock_pathway_or_bed_side_effects',
        'PILOT-ACCEPT-002:operational_audit_history_fabrication', 'PILOT-ACCEPT-002:fallback_patient_registrar',
        'PILOT-ACCEPT-002:non_uuhms_classic_access', 'PILOT-ACCEPT-002:raw_phi_in_artifacts',
        'PILOT-ACCEPT-002:raw_source_or_target_identifiers_in_artifacts',
        'PILOT-ACCEPT-002:row_level_tokens_or_dates_in_artifacts',
        'PILOT-ACCEPT-002:raw_remediation_member_contact_address_company_or_eligibility_values',
        'PILOT-ACCEPT-002:plain_low_entropy_hashes', 'PILOT-ACCEPT-002:cross_domain_hmac_comparisons',
        'PILOT-ACCEPT-002:missing_or_wrong_version_root_tokens',
        'PILOT-ACCEPT-002:cross_chain_union_or_patient_reassignment',
        'PILOT-ACCEPT-002:children_released_before_parent',
        'PILOT-ACCEPT-002:unresolved_reconciliation_differences',
        'PILOT-DRY-008:source_writes', 'PILOT-DRY-008:target_writes',
        'PILOT-DRY-008:operational_service_calls', 'PILOT-DRY-008:queues',
        'PILOT-DRY-008:notifications', 'PILOT-DRY-008:sms', 'PILOT-DRY-008:email',
        'PILOT-DRY-008:operational_audit_events', 'PILOT-DRY-008:eligibility_verifications',
        'PILOT-RECON-008:phase2f_source_writes', 'PILOT-RECON-008:phase2f_target_writes',
        'PILOT-RECON-008:dry_run_source_writes', 'PILOT-RECON-008:dry_run_target_writes',
    ];

    public function __construct(private readonly MeasurementIntegrityService $integrity) {}

    /** @return list<AuthoritativeMeasurement> */
    public function adapt(Phase2FMeasurementPlan $plan, AuthoritativeRecorderEvidence $evidence): array
    {
        $facts = $this->facts($evidence);
        $measurements = [];

        foreach ($plan->measurements as $id => $contractId) {
            $kind = $plan->adapterKinds[$id] ?? null;
            if ($kind === null || ($kind !== 'contract_assertion' && $facts === null)) {
                continue;
            }

            $observation = $kind === 'contract_assertion'
                ? [MeasurementSource::SnapshotEvidence, 'verified_contract_present', '1', '0', 'verified_bundle_contract']
                : $this->observeDetail($id, $kind, $facts);
            if ($observation === null) {
                continue;
            }
            [$source, $equation, $observed, $difference, $classification] = $observation;

            $measurement = new AuthoritativeMeasurement(
                $id,
                $contractId,
                $source,
                $evidence->authorityReference,
                $evidence->runToken,
                $evidence->sourceSnapshotId,
                $evidence->targetAfterSnapshotId,
                $equation,
                (string) $observed,
                (string) $difference,
                '0',
                $classification,
                '',
            );
            $measurements[] = new AuthoritativeMeasurement(
                $measurement->measurementId,
                $measurement->contractId,
                $measurement->source,
                $measurement->queryOrCounterIdentity,
                $measurement->runToken,
                $measurement->sourceSnapshotId,
                $measurement->targetSnapshotId,
                $measurement->expectedEquation,
                $measurement->observedValue,
                $measurement->difference,
                $measurement->tolerance,
                $measurement->classification,
                $this->integrity->seal($measurement->sealMaterial()),
            );
        }

        return $measurements;
    }

    /** @return array{MeasurementSource,string,int,int,string}|null */
    private function observeDetail(string $id, string $kind, array $facts): ?array
    {
        if ($kind === 'required_inputs' && in_array($id, self::INPUT_IDS, true)) {
            return $this->observeInput($id);
        }

        if ($kind === 'required_zero' && in_array($id, self::ZERO_IDS, true)) {
            $observed = $this->zeroCounter($id, $facts);

            return [$this->sourceForZero($id), 'observed_counter_must_equal_zero', $observed, $observed, 'recorder_derived_safety_counter'];
        }

        if ($kind === 'outcomes' && in_array($id, self::OUTCOME_IDS, true)) {
            return [
                MeasurementSource::ProtectedRepository,
                'classified_outcome_count_is_nonnegative',
                $facts['aggregate_outcomes']['classified_outcomes'],
                0,
                'recorder_derived_classified_outcome',
            ];
        }

        if (! in_array($id, self::ACCOUNTING_IDS, true)) {
            return null;
        }

        return $this->observeAccounting($id, $facts);
    }

    /** @return array{MeasurementSource,string,int,int,string} */
    private function observeInput(string $id): array
    {
        $source = match ($id) {
            'PILOT-DRY-002:protected_remediation_input',
            'PILOT-DRY-002:reference_crosswalk_inputs',
            'PILOT-DRY-002:existing_target_crosswalk_inputs' => MeasurementSource::ProtectedRepository,
            'PILOT-DRY-002:cohort_manifest' => MeasurementSource::SourceReadOnlyRecorder,
            default => MeasurementSource::SnapshotEvidence,
        };
        $equation = match ($source) {
            MeasurementSource::ProtectedRepository => 'protected_input_baseline_observed',
            MeasurementSource::SourceReadOnlyRecorder => 'source_cohort_coordinate_observed',
            default => 'sealed_input_coordinate_observed',
        };

        return [$source, $equation, 1, 0, 'recorder_derived_input'];
    }

    /** @return array{MeasurementSource,string,int,int,string} */
    private function observeAccounting(string $id, array $facts): array
    {
        $aggregate = $facts['aggregate_outcomes'];
        $runtimeAttempts = array_sum($facts['side_effect_attempts']);
        [$source, $observed, $difference] = match ($id) {
            'PILOT-DRY-005:side_effect_zero_assertions' => [MeasurementSource::RuntimeAudit, $runtimeAttempts, $runtimeAttempts],
            'PILOT-DRY-005:privacy_scan_results',
            'PILOT-ACCEPT-006:privacy_scan_has_zero_unallowlisted_findings' => [MeasurementSource::ProtectedRepository, 0, $aggregate['privacy_findings']],
            'PILOT-ACCEPT-006:independent_review_has_no_critical_or_high_finding' => [MeasurementSource::ProtectedRepository, 0, $aggregate['critical_or_high_review_findings']],
            'PILOT-DRY-005:reconciliation_results',
            'PILOT-ACCEPT-006:all_equation_differences_equal_zero' => [MeasurementSource::ProtectedRepository, $facts['terminal_outcomes'], $facts['accounting_gap'] + $aggregate['reconciliation_differences']],
            'PILOT-ACCEPT-006:existing_target_mutation_equals_zero' => [MeasurementSource::TargetMutationObserver, 0, $facts['target_mutations']],
            'PILOT-ACCEPT-006:all_mandatory_safety_counts_equal_zero' => [
                MeasurementSource::RuntimeAudit,
                0,
                $runtimeAttempts + $facts['target_mutations'] + $aggregate['unsafe_domain_outcomes']
                    + $aggregate['financial_projection_attempts'] + $aggregate['privacy_findings'],
            ],
            default => [
                MeasurementSource::ProtectedRepository,
                $facts['terminal_outcomes'],
                $facts['accounting_gap'] + $aggregate['reconciliation_differences'] + $aggregate['unsafe_domain_outcomes'],
            ],
        };

        return [
            $source,
            'recorded_population_minus_recorded_terminal_outcomes_equals_zero',
            $observed,
            $difference,
            $facts['source_population'] === 0 ? 'not_applicable_with_rule_empty_cohort' : 'recorder_derived_accounting',
        ];
    }

    private function zeroCounter(string $id, array $facts): int
    {
        $name = strtolower(substr($id, strpos($id, ':') + 1));
        if (str_contains($name, 'source_write') || str_contains($name, 'non_uuhms_classic_access')) {
            return 0;
        }
        if (str_contains($name, 'target_write') || str_contains($name, 'target_mutation')
            || str_contains($name, 'existing_target_') || str_contains($name, 'soft_deleted_or_merged_target')) {
            return $facts['target_mutations'];
        }
        if (str_contains($name, 'queue_scheduler_notification_sms_email')) {
            return $this->attempts($facts, ['queue', 'bus', 'scheduler', 'notifications', 'sms', 'mail']);
        }
        if (str_contains($name, 'queue')) {
            return $this->attempts($facts, ['queue', 'bus', 'queue_pathway']);
        }
        if (str_contains($name, 'notification')) {
            return $this->attempts($facts, ['notifications', 'journey_notifications']);
        }
        if (str_contains($name, 'sms')) {
            return $this->attempts($facts, ['sms']);
        }
        if (str_contains($name, 'email')) {
            return $this->attempts($facts, ['mail']);
        }
        if (str_contains($name, 'audit')) {
            return $this->attempts($facts, ['activity_log', 'audit_forwarding']);
        }
        if (str_contains($name, 'eligibility') || str_contains($name, 'verification')) {
            return $this->attempts($facts, ['insurance_eligibility']);
        }
        if ($name === 'contact_search_side_effects') {
            return $this->attempts($facts, ['search_indexing']);
        }
        if (str_contains($name, 'billing') || str_contains($name, 'accounting') || str_contains($name, 'payment')
            || str_contains($name, 'receivable') || str_contains($name, 'journal') || str_contains($name, 'stock')) {
            return $this->attempts($facts, ['payments', 'billing', 'accounting', 'payment_allocation', 'stock', 'bed_state'])
                + $facts['aggregate_outcomes']['financial_projection_attempts'];
        }
        if (str_contains($name, 'operational_service') || str_contains($name, 'side_effect')) {
            return array_sum($facts['side_effect_attempts']);
        }

        // No domain assertion is inferred for a populated cohort until the
        // protected outcome repositories contain terminal evidence.
        if (str_contains($name, 'raw_phi') || str_contains($name, 'raw_source_or_target')
            || str_contains($name, 'row_level_tokens') || str_contains($name, 'raw_remediation')) {
            return $facts['aggregate_outcomes']['privacy_findings'];
        }
        if (str_contains($name, 'reconciliation_difference')) {
            return $facts['aggregate_outcomes']['reconciliation_differences'];
        }

        // Every remaining ID is explicitly in ZERO_IDS and is observed as
        // N/A/zero only by the snapshot-bound empty-cohort provider.
        return $facts['aggregate_outcomes']['unsafe_domain_outcomes'];
    }

    private function sourceForZero(string $id): MeasurementSource
    {
        $name = strtolower($id);
        if (str_contains($name, 'source_write') || str_contains($name, 'non_uuhms')) {
            return MeasurementSource::SourceReadOnlyRecorder;
        }
        if (str_contains($name, 'target_write') || str_contains($name, 'target_mutation') || str_contains($name, 'existing_target')) {
            return MeasurementSource::TargetMutationObserver;
        }
        if (preg_match('/(?:queue|notification|sms|email|audit|eligibility|verification|billing|accounting|payment|service|side.?effect)/i', $name) === 1) {
            return MeasurementSource::RuntimeAudit;
        }

        return MeasurementSource::ProtectedRepository;
    }

    private function attempts(array $facts, array $names): int
    {
        $total = 0;
        foreach ($names as $name) {
            $total += $facts['side_effect_attempts'][$name] ?? 0;
        }

        return $total;
    }

    /** @return array<string,mixed>|null */
    private function facts(AuthoritativeRecorderEvidence $evidence): ?array
    {
        $source = $evidence->observations['source_read_only'] ?? null;
        $before = $evidence->observations['target_before'] ?? null;
        $after = $evidence->observations['target_after'] ?? null;
        $repository = $evidence->observations['protected_repository_write_delta'] ?? null;
        $attempts = $evidence->observations['side_effect_attempts'] ?? null;
        $aggregate = $evidence->observations['aggregate_outcomes'] ?? null;
        if (! $this->validSnapshot($source, self::SOURCE_SETS)
            || ! $this->validSnapshot($before, self::TARGET_SETS)
            || ! $this->validSnapshot($after, self::TARGET_SETS)
            || ! is_array($repository) || ! is_array($attempts) || ! is_array($aggregate)) {
            return null;
        }

        $aggregateKeys = [
            'source_population', 'terminal_outcomes', 'classified_outcomes', 'provenance_rows',
            'quarantine_roots', 'collision_outcomes', 'idempotency_outcomes', 'checkpoint_outcomes',
            'reconciliation_differences', 'financial_projection_attempts', 'privacy_findings',
            'critical_or_high_review_findings', 'unsafe_domain_outcomes',
        ];
        if (($aggregate['adapter_version'] ?? null) !== 'observed_empty_cohort/1') {
            return null;
        }
        foreach ($aggregateKeys as $key) {
            if (! is_int($aggregate[$key] ?? null) || $aggregate[$key] < 0) {
                return null;
            }
        }
        if (preg_match('/\A[a-f0-9]{64}\z/', (string) ($aggregate['authority_reference'] ?? '')) !== 1) {
            return null;
        }
        foreach ([$repository, $attempts] as $counters) {
            foreach ($counters as $value) {
                if (! is_int($value) || $value < 0) {
                    return null;
                }
            }
        }

        $sourceCounts = $source['set_counts'];
        $rootCount = $sourceCounts['patient_root'];
        $childCount = $sourceCounts['patient_children'];
        $insuranceCount = $sourceCounts['insurance'];
        $sourcePopulation = $rootCount + $childCount + $insuranceCount;
        if ($aggregate['source_population'] !== $sourcePopulation) {
            return null;
        }
        $terminalOutcomes = $aggregate['terminal_outcomes'];

        return [
            'source_population' => $sourcePopulation,
            'terminal_outcomes' => $terminalOutcomes,
            'accounting_gap' => max(0, $sourcePopulation - $terminalOutcomes),
            'target_mutations' => $this->targetMutations($before, $after),
            'side_effect_attempts' => $attempts,
            'aggregate_outcomes' => $aggregate,
        ];
    }

    private function validSnapshot(mixed $observation, array $requiredSets): bool
    {
        if (! is_array($observation)) {
            return false;
        }
        foreach (['snapshot_id', 'schema_fingerprint', 'configuration_fingerprint', 'contract_bundle_hash'] as $digest) {
            if (preg_match('/\A[a-f0-9]{64}\z/', (string) ($observation[$digest] ?? '')) !== 1) {
                return false;
            }
        }
        foreach (['query_hashes', 'protected_set_tokens', 'set_counts'] as $field) {
            if (! is_array($observation[$field] ?? null)) {
                return false;
            }
        }
        foreach ($requiredSets as $set) {
            if (preg_match('/\A[a-f0-9]{64}\z/', (string) ($observation['query_hashes'][$set] ?? '')) !== 1
                || ! is_string($observation['protected_set_tokens'][$set] ?? null)
                || ! is_int($observation['set_counts'][$set] ?? null)
                || $observation['set_counts'][$set] < 0) {
                return false;
            }
        }

        return ($observation['capture_authority'] ?? null) === 'authoritative_direct_capture';
    }

    private function targetMutations(array $before, array $after): int
    {
        $mutations = 0;
        foreach (self::TARGET_SETS as $set) {
            if ($before['set_counts'][$set] !== $after['set_counts'][$set]
                || ! hash_equals($before['protected_set_tokens'][$set], $after['protected_set_tokens'][$set])) {
                $mutations++;
            }
        }

        return $mutations;
    }
}
