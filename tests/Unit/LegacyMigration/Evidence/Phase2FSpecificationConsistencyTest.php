<?php

namespace Tests\Unit\LegacyMigration\Evidence;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class Phase2FSpecificationConsistencyTest extends TestCase
{
    private const DOCUMENTS = [
        'README.md', 'PHASE_2F_SCOPE_BOUNDARY.md', 'PATIENT_PILOT_COHORT_CONTRACT.md',
        'PATIENT_PILOT_SCENARIO_MATRIX.md', 'PATIENT_PILOT_SOURCE_COHORT_SELECTION.md',
        'PATIENT_PILOT_REMEDIATION_INPUT_CONTRACT.md', 'PATIENT_PILOT_STATE_MATRIX.md',
        'INSURANCE_PILOT_INITIALIZATION_MATRIX.md', 'PATIENT_PILOT_COMPOSED_COLUMN_MAPPING.md',
        'PATIENT_PILOT_DEPENDENCY_GRAPH.md', 'PATIENT_PILOT_EXECUTION_SEQUENCE.md',
        'PATIENT_PILOT_ATOMICITY_CONTRACT.md', 'PATIENT_PILOT_CROSSWALK_INTERFACE.md',
        'PATIENT_PILOT_PROVENANCE_INTERFACE.md', 'PATIENT_PILOT_QUARANTINE_INTERFACE.md',
        'PATIENT_PILOT_RECONCILIATION_INTERFACE.md', 'PATIENT_PILOT_IDEMPOTENCY_CONTRACT.md',
        'PATIENT_PILOT_DRY_RUN_CONTRACT.md', 'PATIENT_PILOT_TARGET_COLLISION_CONTRACT.md',
        'PATIENT_PILOT_EXCEPTION_FLOW.md', 'PATIENT_PILOT_ROLLBACK_RESUME_CONTRACT.md',
        'PATIENT_PILOT_PRIVACY_CONTRACT.md', 'PATIENT_PILOT_ACCEPTANCE_CRITERIA.md',
        'PHASE_3_FOUNDATION_HANDOFF.md', 'PATIENT_PILOT_MAPPING_READINESS_MATRIX.md',
        'PHASE_2F_EXIT_REPORT.md',
    ];

    private const SPECIFICATIONS = [
        'patient_pilot_cohort_rules.json', 'patient_pilot_scenarios.json',
        'patient_pilot_source_selection_rules.json', 'patient_pilot_remediation_input_rules.json',
        'patient_pilot_state_matrix.json', 'insurance_pilot_initialization_rules.json',
        'patient_pilot_composed_column_mappings.json', 'patient_pilot_dependency_sequence.json',
        'patient_pilot_atomicity_rules.json', 'patient_pilot_crosswalk_interface.json',
        'patient_pilot_provenance_interface.json', 'patient_pilot_quarantine_interface.json',
        'patient_pilot_reconciliation_interface.json', 'patient_pilot_idempotency_rules.json',
        'patient_pilot_dry_run_contract.json', 'patient_pilot_target_collision_rules.json',
        'patient_pilot_exception_precedence.json', 'patient_pilot_rollback_resume_rules.json',
        'patient_pilot_privacy_contract.json', 'patient_pilot_acceptance_thresholds.json',
        'phase3_foundation_requirements.json',
    ];

    #[Test]
    public function required_package_is_complete_parseable_and_non_executable(): void
    {
        $documents = array_map('basename', glob($this->root('docs/legacy-migration/phase-2f/*.md')) ?: []);
        sort($documents);
        $expectedDocuments = self::DOCUMENTS;
        sort($expectedDocuments);
        $this->assertSame($expectedDocuments, $documents);

        $specifications = array_map('basename', glob($this->root('docs/legacy-migration/phase-2f/specifications/*.json')) ?: []);
        sort($specifications);
        $expectedSpecifications = self::SPECIFICATIONS;
        sort($expectedSpecifications);
        $this->assertSame($expectedSpecifications, $specifications);

        foreach ($specifications as $name) {
            $spec = $this->json($name);
            $this->assertSame('2F.1.0', $spec['specification_version'], $name);
            $this->assertSame('2F', $spec['phase'], $name);
            $this->assertSame('legacy_uhms', $spec['approved_source']['connection'], $name);
            $this->assertSame('uuhms', $spec['approved_source']['database'], $name);
            $this->assertTrue($spec['approved_source']['read_only'], $name);
            $this->assertTrue($spec['approved_target']['read_only'], $name);
            $this->assertFalse($spec['implementation_authorized'], $name);
            $this->assertFalse($spec['privacy']['contains_raw_phi'], $name);
            $this->assertNotEmpty($spec['upstream_contracts'], $name);
            $this->assertSame($spec['record_count'], count($spec['records']), $name);
        }
    }

    #[Test]
    public function composed_mapping_has_one_authoritative_owner_for_each_of_33_source_fields(): void
    {
        $spec = $this->json('patient_pilot_composed_column_mappings.json');
        $records = $spec['records'];
        $keys = [];
        $owners = ['2C' => 0, '2D' => 0, '2E' => 0];
        foreach ($records as $record) {
            $key = implode('.', [$record['source']['schema'], $record['source']['table'], $record['source']['column']]);
            $keys[] = $key;
            $owner = $record['authoritative_owner'];
            $owners[$owner['phase']]++;
            $this->assertSame($record['source_contract_id'], $owner['contract_id'], $record['id']);
            foreach (['transformation_contract_ids', 'exception_ids', 'reconciliation_ids', 'privacy_rule_ids', 'extraction_rule_ids', 'commit_blockers'] as $field) {
                $this->assertNotEmpty($record[$field], $record['id'].':'.$field);
            }
            $this->assertNotEmpty($record['future_atomic_stage'], $record['id']);
            $this->assertNotEmpty($record['commit_eligibility'], $record['id']);
            $this->assertNotEmpty($record['rollback_scope'], $record['id']);
        }
        $this->assertCount(33, $records);
        $this->assertCount(33, array_unique($keys));
        $this->assertSame(['2C' => 15, '2D' => 7, '2E' => 11], $owners);
        $this->assertSame(24, count(array_filter($records, fn (array $record): bool => $record['source']['table'] === 'patients')));
        $this->assertSame(9, count(array_filter($records, fn (array $record): bool => $record['source']['table'] === 'insurance')));
    }

    #[Test]
    public function all_66_synthetic_scenarios_are_complete_and_contiguous(): void
    {
        $spec = $this->json('patient_pilot_scenarios.json');
        $records = $spec['records'];
        $this->assertCount(66, $records);
        $this->assertSame(array_map(fn (int $i): string => sprintf('A-%03d', $i), range(1, 66)), array_column($records, 'id'));
        $this->assertCount(66, array_unique(array_column($records, 'scenario')));
        $patientFields = ['PAT_ID', 'PatientName', 'OpdNo', 'Sex', 'DOB', 'PhoneNo', 'Work', 'Company', 'Address', 'NOK', 'NOKPhoneNo', 'NOKRel', 'Religion', 'MaritalStatus', 'BillStatus', 'Refill', 'Allergies', 'Medication', 'History', 'LastVisit', 'EditDate', 'RegDate', 'OriginalName', 'OriginalOpd'];
        $insuranceFields = ['INS_ID', 'PAT_ID', 'InsType', 'Scheme', 'MemberNo', 'Company', 'IssueDate', 'ExpiryDate', 'Plan'];
        $safetyNames = array_column($this->json('patient_pilot_acceptance_thresholds.json')['records'], null, 'class')['mandatory_safety_zero']['required_zero'];
        $contractUniverse = $this->upstreamText().' '.implode(' ', $this->phase2fRecordIds());
        foreach ($records as $record) {
            $this->assertSame('SYNTHETIC-ONLY-P2F', $record['fixture_namespace'], $record['id']);
            $this->assertStringContainsString('obviously_fictitious', $record['synthetic_only_assertion'], $record['id']);
            foreach (['patient_rows', 'opd_alias_context', 'child_and_demographic_rows', 'insurance_rows', 'protected_remediation', 'target_comparison', 'fault_injection', 'orphan_dependency_rows', 'cardinalities'] as $field) {
                $this->assertArrayHasKey($field, $record['inputs'], $record['id'].':'.$field);
            }
            foreach ($record['inputs']['patient_rows'] as $patient) {
                $actual = array_keys($patient['fields']);
                $this->assertSame($patientFields, $actual, $record['id'].':patient fields');
            }
            foreach ($record['inputs']['insurance_rows'] as $insurance) {
                $this->assertSame($insuranceFields, array_keys($insurance['fields']), $record['id'].':insurance fields');
            }
            $this->assertSame(count($record['inputs']['patient_rows']), $record['inputs']['cardinalities']['patient_rows'], $record['id']);
            $this->assertSame(count($record['inputs']['insurance_rows']), $record['inputs']['cardinalities']['insurance_rows'], $record['id']);

            foreach (['patient', 'alias', 'child', 'insurance', 'exception_precedence', 'provenance_cardinality', 'reconciliation', 'safety_zero_expectations'] as $field) {
                $this->assertArrayHasKey($field, $record['expected'], $record['id'].':'.$field);
            }
            foreach (['patient', 'alias', 'child', 'insurance'] as $domain) {
                $this->assertNotEmpty($record['expected'][$domain]['disposition'], $record['id'].':'.$domain);
            }
            $exceptions = $record['expected']['exception_precedence'];
            $this->assertSame(count($exceptions['ordered']), $exceptions['classified_exception_count'], $record['id']);
            $this->assertSame($exceptions['ordered'][0]['code'] ?? null, $exceptions['primary_exception'], $record['id']);
            $this->assertTrue($record['expected']['provenance_cardinality']['every_consumed_field_has_one_primary_disposition'], $record['id']);
            $this->assertTrue($record['expected']['provenance_cardinality']['protected_only_no_repository_values'], $record['id']);

            $reconciliation = $record['expected']['reconciliation'];
            $this->assertSame(count($reconciliation['contracts']), $reconciliation['contract_count'], $record['id']);
            foreach ($reconciliation['contracts'] as $contract) {
                $this->assertNotEmpty($contract['contract_id'], $record['id']);
                $this->assertStringContainsString($contract['contract_id'], $contractUniverse, $record['id'].':'.$contract['contract_id']);
                $this->assertNotEmpty($contract['instantiated_equation'], $record['id'].':'.$contract['contract_id']);
                $this->assertSame(0, $contract['difference'], $record['id'].':'.$contract['contract_id']);
                $this->assertSame(0, $contract['tolerance'], $record['id'].':'.$contract['contract_id']);
            }
            $this->assertTrue($reconciliation['all_differences_zero'], $record['id']);
            $safety = $record['expected']['safety_zero_expectations'];
            $this->assertSame(49, $safety['measure_count'], $record['id']);
            $this->assertSame($safetyNames, array_keys($safety['values']), $record['id']);
            foreach ($safety['values'] as $name => $value) {
                $this->assertSame(0, $value, $record['id'].':'.$name);
            }
            $this->assertTrue($safety['all_zero'], $record['id']);
            $this->assertSame(0, $record['expected']['phase_2f_database_writes'], $record['id']);
            $this->assertFalse($record['expected']['implementation_authorized'], $record['id']);
        }
    }

    #[Test]
    public function cohort_b_is_bounded_deterministic_and_uses_resolved_predicates(): void
    {
        $spec = $this->json('patient_pilot_source_selection_rules.json');
        $records = $spec['records'];
        $this->assertCount(24, $records);
        $this->assertSame(range(1, 24), array_column($records, 'order'));
        $this->assertSame(148, array_sum(array_column($records, 'quota')));
        $this->assertSame(148, $spec['maximum_primary_roots']);
        $this->assertSame(0, $spec['future_commit_ready_count']);
        $this->assertFalse($spec['dry_run_ready']);
        $this->assertSame(24, $spec['required_predicate_count']);
        $this->assertSame(0, $spec['frozen_predicate_count']);
        $this->assertStringContainsString('BLOCKED_PENDING', $spec['selection_freeze_status']);
        $this->assertSame('required_before_selection', $spec['predicate_contract_requirement']['normalized_predicate_sha256']);
        $this->assertStringContainsString('min(q_s,A_s_after_prior_assignment)', $spec['exact_size_formula']);

        $upstream = $this->upstreamText();
        foreach ($records as $record) {
            $this->assertStringContainsString($record['predicate_owner'], $upstream, $record['id']);
        }
    }

    #[Test]
    public function required_patient_and_insurance_initial_state_is_fail_closed(): void
    {
        $patient = $this->json('patient_pilot_state_matrix.json');
        $this->assertFalse($patient['defaults_are_migration_authority']);
        $patientByField = array_column($patient['records'], null, 'field');
        $requiredAttributes = ['installed_type', 'allowed_values', 'application_semantics', 'installed_default', 'default_safety_rationale', 'classic_evidence', 'approved_policy', 'approved_target_initialization', 'technical_recommendation', 'technical_recommendation_rationale', 'approval_state', 'existing_target_behavior', 'unresolved_exception_code', 'reconciliation', 'phase3_requirement'];
        foreach ($patient['records'] as $record) {
            foreach ($requiredAttributes as $attribute) {
                $this->assertArrayHasKey($attribute, $record, $record['id'].':'.$attribute);
            }
            $this->assertNotEmpty($record['application_semantics'], $record['id']);
            $this->assertNotEmpty($record['default_safety_rationale'], $record['id']);
            $this->assertNotEmpty($record['classic_evidence'], $record['id']);
            $this->assertNotEmpty($record['phase3_requirement'], $record['id']);
        }
        foreach (['status', 'is_active', 'is_temporary', 'merge_status', 'is_deceased'] as $field) {
            $this->assertArrayHasKey($field, $patientByField);
            $this->assertStringContainsString('BLOCKING', $patientByField[$field]['approval_state']);
        }
        $this->assertSame('COMMIT_BLOCKED_UNTIL_APPROVED', $patientByField['complete_state_tuple']['approval_state']);

        $insurance = $this->json('insurance_pilot_initialization_rules.json');
        $this->assertFalse($insurance['defaults_are_migration_authority']);
        $insuranceByField = array_column($insurance['records'], null, 'field');
        foreach (['member_type', 'is_active'] as $field) {
            $this->assertSame('COMMIT_BLOCKER_TARGET_REPRESENTATION', $insuranceByField[$field]['approval_state']);
        }
        foreach (['insurance_tier_id', 'card_holder_insurance_id', 'policy_number', 'ccc_code'] as $field) {
            $this->assertSame('UPSTREAM_REQUIRED_NULL_NOT_EVIDENCED', $insuranceByField[$field]['approval_state']);
            $this->assertStringContainsString('null/not evidenced exactly as required by INS-CONS-010', $insuranceByField[$field]['rule']);
        }
        $this->assertSame('PROHIBITED_BY_INS-ELIG-001_TO_006', $insuranceByField['verification_and_eligibility']['approval_state']);
        $this->assertSame('COMMIT_BLOCKED', $insuranceByField['current_representation']['approval_state']);
    }

    #[Test]
    public function dependency_sequence_seals_identity_before_all_children_and_insurance(): void
    {
        $spec = $this->json('patient_pilot_dependency_sequence.json');
        $records = $spec['records'];
        $this->assertSame(range(0, 23), array_column($records, 'stage'));
        $this->assertSame(11, $spec['identity_invariant']['identity_envelope_sealed_at_stage']);
        $this->assertFalse($spec['identity_invariant']['later_stage_rewrite_permitted']);
        foreach ($records as $record) {
            if ($record['stage'] >= 12) {
                $this->assertTrue($record['requires_unchanged_identity_seal'], $record['id']);
            }
        }
        $byStage = array_column($records, null, 'stage');
        $this->assertStringNotContainsString('state outcome unresolved for commit', implode(' ', $byStage[10]['stop_conditions']));
        $this->assertStringNotContainsString('initialization unresolved', implode(' ', $byStage[18]['stop_conditions']));
    }

    #[Test]
    public function future_atomicity_idempotency_and_rollback_are_explicit(): void
    {
        $records = $this->json('patient_pilot_atomicity_rules.json')['records'];
        $units = array_column($records, 'unit');
        foreach (['A_PATIENT_CORE', 'B_OPD_ALIAS', 'C_EMERGENCY_CONTACT', 'D_INSURANCE_HISTORY', 'D_INSURANCE_CURRENT'] as $unit) {
            $this->assertContains($unit, $units);
        }
        foreach ($records as $record) {
            $this->assertTrue($record['idempotency_required'], $record['id']);
            $this->assertNotEmpty($record['rollback'], $record['id']);
        }
    }

    #[Test]
    public function reconciliation_and_acceptance_require_exact_classification_and_safety_zeroes(): void
    {
        $reconciliation = $this->json('patient_pilot_reconciliation_interface.json');
        $this->assertSame(0, $reconciliation['required_tolerance']);
        $acceptance = $this->json('patient_pilot_acceptance_thresholds.json');
        $this->assertSame(0, $acceptance['required_tolerance']);
        $byClass = array_column($acceptance['records'], null, 'class');
        $this->assertSame(100, $byClass['mandatory_zero_difference']['required_percent']);
        $this->assertSame(0, $byClass['mandatory_zero_difference']['difference']);
        foreach (['source_writes', 'target_writes_during_specification_or_dry_run', 'automatic_patient_merges', 'invented_patient_values', 'invented_actors', 'existing_target_identity_changes', 'unresolved_reconciliation_differences'] as $zero) {
            $this->assertContains($zero, $byClass['mandatory_safety_zero']['required_zero']);
        }
        $this->assertNotEmpty($byClass['allowed_nonzero_classified_outcomes']['outcomes']);
        $this->assertSame('NOT_AUTHORIZED_FOR_EXECUTION', $byClass['pilot_entry_criteria']['phase_2f_entry_verdict']);
        $entry = implode(' ', array_merge($byClass['pilot_entry_criteria']['future_dry_run_required'], $byClass['pilot_entry_criteria']['future_commit_additional_required']));
        foreach (['d101', 'uuhms', 'remediation', 'patient_state', 'insurance_member_type', 'patient_number_allocator', 'side_effect_isolation', 'idempotency', 'collision', 'commit_pilot_authorization'] as $blocker) {
            $this->assertStringContainsString($blocker, $entry, $blocker);
        }
        $this->assertSame('DRY_RUN_ACCEPTED_NOT_COMMIT_AUTHORIZED', $byClass['pilot_exit_criteria']['dry_run_exit_verdict']);
    }

    #[Test]
    public function quarantine_and_target_collision_branches_preserve_one_root_and_zero_mutation(): void
    {
        $quarantine = array_column($this->json('patient_pilot_quarantine_interface.json')['records'], null, 'id');
        $this->assertSame([
            'patient' => 'PATIENT-PRIV-007',
            'orphan_attendance' => 'PATIENT-PRIV-008',
            'orphan_insurance' => 'PATIENT-PRIV-009',
        ], $quarantine['PILOT-QUAR-002']['root_domains']);
        $this->assertStringContainsString('exactly one authoritative typed root', $quarantine['PILOT-QUAR-002']['rule']);
        $this->assertStringContainsString('No descendant releases before its valid parent', $quarantine['PILOT-QUAR-006']['rule']);

        $collisions = $this->json('patient_pilot_target_collision_rules.json')['records'];
        $this->assertCount(16, $collisions);
        $text = implode(' ', array_column($collisions, 'outcome'));
        foreach (['never fall through to create', 'never restore/replace', 'preserve target', 'zero enrichment', 'immutable conflict', 'never dedupe/overwrite'] as $invariant) {
            $this->assertStringContainsString($invariant, $text, $invariant);
        }
        $byId = array_column($collisions, null, 'id');
        $this->assertSame('LEGACY-PATIENT-CHILD-CONTACT-012', $byId['PILOT-COLL-012']['exception']);
        $this->assertSame('LEGACY-PATIENT-CHILD-TARGET-036', $byId['PILOT-COLL-016']['exception']);
    }

    #[Test]
    public function exception_records_and_quarantine_chains_have_independent_idempotency_keys(): void
    {
        $records = array_column($this->json('patient_pilot_idempotency_rules.json')['records'], null, 'outcome');
        $this->assertArrayHasKey('exception_record', $records);
        $this->assertArrayHasKey('quarantine_chain', $records);
        $this->assertContains('exception_code', $records['exception_record']['key_inputs']);
        $this->assertNotContains('exception_code', $records['quarantine_chain']['key_inputs']);
        $this->assertContains('typed_authoritative_root_token', $records['quarantine_chain']['key_inputs']);
    }

    #[Test]
    public function every_crash_boundary_has_a_complete_fail_closed_recovery_contract(): void
    {
        $spec = $this->json('patient_pilot_rollback_resume_rules.json');
        $this->assertSame(10, $spec['required_boundary_count']);
        $this->assertCount(10, $spec['records']);
        $this->assertFalse($spec['hard_delete_is_universal_rollback']);
        $required = ['durable_facts', 'safe_retry', 'duplicate_prevention', 'rollback_feasibility', 'compensation_requirement', 'checkpoint_behavior', 'reconciliation_proof', 'operator_action', 'disposition'];
        foreach ($spec['records'] as $record) {
            foreach ($required as $field) {
                $this->assertNotEmpty($record[$field], $record['id'].':'.$field);
            }
        }
        $this->assertCount(24, array_unique($spec['states']));
    }

    #[Test]
    public function every_referenced_legacy_exception_code_resolves_to_phase_2a_through_2e(): void
    {
        $known = $this->upstreamText();
        $references = [];
        foreach (self::SPECIFICATIONS as $name) {
            preg_match_all('/LEGACY-[A-Z0-9-]+/', (string) file_get_contents($this->specPath($name)), $matches);
            $references = array_merge($references, $matches[0]);
        }
        foreach (array_unique($references) as $reference) {
            $this->assertStringContainsString($reference, $known, $reference);
        }
    }

    #[Test]
    public function every_declared_upstream_contract_resolves_to_phase_2a_through_2e(): void
    {
        $upstream = $this->upstreamText();
        $phase2fIds = $this->phase2fRecordIds();
        foreach (self::SPECIFICATIONS as $name) {
            foreach ($this->json($name)['upstream_contracts'] as $reference) {
                $segments = explode(':', $reference);
                $id = end($segments);
                if (str_starts_with($id, 'PILOT-') || str_starts_with($id, 'P2F-')) {
                    $this->assertContains($id, $phase2fIds, $name.':'.$reference);
                } else {
                    $this->assertStringContainsString($id, $upstream, $name.':'.$reference);
                }
            }
        }
    }

    #[Test]
    public function privacy_scan_covers_the_complete_migration_artifact_package(): void
    {
        $root = $this->root('docs/legacy-migration');
        $files = [];
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($root, \FilesystemIterator::SKIP_DOTS),
        );
        foreach ($iterator as $file) {
            if ($file->isFile()) {
                $files[] = $file->getPathname();
            }
        }
        $files[] = __FILE__;
        sort($files, SORT_STRING);
        $this->assertNotEmpty($files);

        $relativePaths = array_map(function (string $path): string {
            $relative = substr($path, strlen($this->root('')));

            return str_replace(DIRECTORY_SEPARATOR, '/', ltrim($relative, DIRECTORY_SEPARATOR));
        }, $files);
        sort($relativePaths, SORT_STRING);

        $privacy = $this->json('patient_pilot_privacy_contract.json');
        $scan = $privacy['scan_contract'];
        $evidence = $privacy['scan_evidence'];
        $this->assertSame('P2F-PRIVACY-SCANNER-2', $scan['scanner_version']);
        $this->assertSame('P2F-SYNTHETIC-ALLOWLIST-1', $scan['allowlist_version']);
        $this->assertSame(count($relativePaths), $evidence['file_count']);
        $this->assertSame(hash('sha256', implode("\n", $relativePaths)), $evidence['scan_scope_manifest_hash']);
        $this->assertSame(0, $evidence['coverage_difference']);
        $this->assertSame(0, $evidence['unallowlisted_finding_count']);

        $detectors = [
            'ghana_phone' => '/\b(?:\+233|0(?:2[034567]|5[03459]))\d{7}\b/',
            'email' => '/[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,}/i',
            'assigned_opd_or_member_value' => '/(?:MemberNo|OpdNo)\s*=\s*["\'][^"\']+/i',
            'credential_or_secret_assignment' => '/(?:DB_PASSWORD|password|secret|api[_-]?key)\s*[:=]\s*["\'][^"\']+/i',
            'raw_identifier_property' => '/"(?:raw_patient_id|raw_target_id|raw_source_id|raw_member_number)"\s*:/i',
            'forbidden_phase2f_structured_flag' => '/"(?:implementation_authorized|write_authorized|patient_commit_authorized)"\s*:\s*true/i',
        ];
        foreach ($files as $file) {
            $text = (string) file_get_contents($file);
            foreach ($detectors as $detector => $pattern) {
                $unsafe = preg_match($pattern, $text) === 1;
                $this->assertFalse($unsafe, str_replace('\\', '/', $file).':'.$detector.':redacted');
            }
        }
    }

    #[Test]
    public function phase_3_handoff_contains_exactly_25_capabilities_without_authorizing_commit(): void
    {
        $spec = $this->json('phase3_foundation_requirements.json');
        $this->assertCount(25, $spec['records']);
        $this->assertCount(25, array_unique(array_column($spec['records'], 'capability')));
        $this->assertTrue($spec['phase3_implementation_required']);
        $this->assertFalse($spec['patient_commit_authorized']);
        $phase2fIds = $this->phase2fRecordIds();
        foreach ($spec['records'] as $record) {
            $this->assertNotEmpty($record['phase2f_interfaces'], $record['id']);
            foreach ($record['phase2f_interfaces'] as $id) {
                $this->assertContains($id, $phase2fIds, $record['id'].':'.$id);
            }
        }
    }

    /** @return list<string> */
    private function phase2fRecordIds(): array
    {
        $ids = [];
        foreach (self::SPECIFICATIONS as $name) {
            foreach ($this->json($name)['records'] as $record) {
                if (isset($record['id'])) {
                    $ids[] = $record['id'];
                }
            }
        }

        return array_values(array_unique($ids));
    }

    private function upstreamText(): string
    {
        $text = '';
        foreach (['phase-2a', 'phase-2b', 'phase-2c', 'phase-2d', 'phase-2e'] as $phase) {
            foreach (glob($this->root("docs/legacy-migration/{$phase}/specifications/*.json")) ?: [] as $file) {
                $text .= (string) file_get_contents($file);
            }
        }

        return $text;
    }

    /** @return array<string, mixed> */
    private function json(string $name): array
    {
        return json_decode((string) file_get_contents($this->specPath($name)), true, flags: JSON_THROW_ON_ERROR);
    }

    private function specPath(string $name): string
    {
        return $this->root('docs/legacy-migration/phase-2f/specifications/'.$name);
    }

    private function root(string $relative): string
    {
        return dirname(__DIR__, 4).DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $relative);
    }
}
