<?php

namespace Tests\Unit\LegacyMigration\Evidence;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class Phase2ESpecificationConsistencyTest extends TestCase
{
    private const SPECIFICATIONS = [
        'existing_target_insurance_rules.json', 'insurance_column_mappings.json',
        'insurance_consolidation_rules.json', 'insurance_date_rules.json',
        'insurance_eligibility_rules.json', 'insurance_exception_codes.json',
        'insurance_extraction_strategies.json', 'insurance_history_provenance_rules.json',
        'insurance_member_number_rules.json', 'insurance_membership_classes.json',
        'insurance_privacy_provenance_contract.json', 'insurance_provider_integration_rules.json',
        'insurance_reconciliation_contracts.json', 'insurance_relationship_rules.json',
        'insurance_scheme_rules.json', 'insurance_sentinel_rules.json',
        'insurance_type_crosswalks.json', 'patient_bill_status_crosswalks.json',
        'patient_company_rules.json',
    ];

    #[Test]
    public function required_specs_parse_and_remain_non_executable_uuhms_contracts(): void
    {
        $files = glob($this->root('docs/legacy-migration/phase-2e/specifications/*.json'));
        $this->assertIsArray($files);
        $actual = array_map('basename', $files);
        sort($actual);
        $expected = self::SPECIFICATIONS;
        sort($expected);
        $this->assertSame($expected, $actual);

        foreach ($files as $file) {
            $spec = $this->decode($file);
            $this->assertSame('2E.1.0', $spec['specification_version'], basename($file));
            $this->assertSame('legacy_uhms', $spec['approved_source']['connection'], basename($file));
            $this->assertSame('uuhms', $spec['approved_source']['database'], basename($file));
            $this->assertTrue($spec['approved_source']['read_only'], basename($file));
            $this->assertFalse($spec['implementation_authorized'], basename($file));
            $this->assertSame($spec['record_count'], count($spec['records']), basename($file));
            $this->assertFalse($spec['privacy']['contains_raw_phi'], basename($file));
            $this->assertSame('APPROVED_DECISION_SPECIFICATIONS.md', $spec['policy_authority']['source'], basename($file));
            $this->assertContains('phase-2a.reference_natural_keys:NK-004', $spec['upstream_contracts'], basename($file));
            $this->assertContains('phase-2c.patient_privacy_provenance_contract:PATIENT-PRIV-009', $spec['upstream_contracts'], basename($file));
            $this->assertContains('phase-2d.patient_child_relationship_rules:PATIENT-CHILD-REL-005', $spec['upstream_contracts'], basename($file));
        }
    }

    #[Test]
    public function exactly_the_required_source_columns_are_mapped_once(): void
    {
        $records = $this->json('insurance_column_mappings.json')['records'];
        $this->assertSame([
            'insurance.INS_ID', 'insurance.PAT_ID', 'insurance.InsType', 'insurance.Scheme',
            'insurance.MemberNo', 'insurance.Company', 'insurance.IssueDate',
            'insurance.ExpiryDate', 'insurance.Plan', 'patients.Company', 'patients.BillStatus',
        ], array_column($records, 'source'));
        $this->assertSame([
            'Protected provenance', 'Crosswalk', 'Transform', 'No approved destination',
            'Protected membership identifier', 'Provider crosswalk', 'Date transform',
            'Date transform', 'No approved destination', 'Evidence-only', 'Crosswalk',
        ], array_column($records, 'primary_disposition'));
        $allowed = $this->json('insurance_column_mappings.json')['allowed_primary_dispositions'];
        foreach ($records as $record) {
            $this->assertContains($record['primary_disposition'], $allowed);
            $this->assertNotEmpty($record['transformation_rule']);
            $this->assertNotEmpty($record['reconciliation_ids']);
            $this->assertNotEmpty($record['extraction_id']);
            $this->assertNotEmpty($record['provenance_contract']);
        }
    }

    #[Test]
    public function refreshed_relationship_and_provider_baselines_balance(): void
    {
        $provider = $this->json('insurance_provider_integration_rules.json')['baseline'];
        $this->assertSame(31307, $provider['rows']);
        $this->assertSame(31307, $provider['blank_or_not_evidenced'] + $provider['source_candidate_single'] + $provider['source_candidate_ambiguous'] + $provider['unmatched_nonblank']);
        $this->assertArrayNotHasKey('mapped', $provider);
        $this->assertStringContainsString('not a target provider mapping', strtolower($this->json('insurance_provider_integration_rules.json')['records'][1]['rule']));

        $reconciliation = array_column($this->json('insurance_reconciliation_contracts.json')['records'], null, 'id');
        $this->assertSame([
            'failed' => 0, 'null_reference' => 0, 'zero_reference' => 0,
            'orphan_nonzero' => 316, 'matched_nonzero' => 30991,
        ], $reconciliation['INS-REC-002']['expected_counts']);
        $this->assertSame(0, $reconciliation['INS-REC-002']['required_difference']);
    }

    #[Test]
    public function every_relationship_has_one_field_specific_sentinel_rule(): void
    {
        $relationships = $this->json('insurance_relationship_rules.json')['records'];
        $sentinels = $this->json('insurance_sentinel_rules.json')['records'];
        $byRelationship = [];
        foreach ($sentinels as $sentinel) {
            if ($sentinel['relationship_id'] !== null) {
                $byRelationship[$sentinel['relationship_id']][] = $sentinel['id'];
            }
        }
        $this->assertCount(10, $relationships);
        foreach ($relationships as $relationship) {
            $this->assertSame([$relationship['sentinel_rule_id']], $byRelationship[$relationship['id']] ?? []);
            $this->assertFalse($relationship['artificial_parent']);
        }
    }

    #[Test]
    public function consolidation_and_eligibility_are_fail_closed(): void
    {
        $rules = array_column($this->json('insurance_consolidation_rules.json')['records'], 'rule', 'id');
        $this->assertStringContainsString('At most one', $rules['INS-CONS-002']);
        $this->assertStringContainsString('must not be applied', $rules['INS-CONS-008']);

        $eligibility = implode(' ', array_column($this->json('insurance_eligibility_rules.json')['records'], 'rule'));
        $this->assertStringContainsString('No insurance_verifications row is created', $eligibility);
        $this->assertStringContainsString('Do not infer', $eligibility);
    }

    #[Test]
    public function existing_target_rules_authorize_no_mutation(): void
    {
        foreach ($this->json('existing_target_insurance_rules.json')['records'] as $record) {
            if (array_key_exists('allowed_change', $record)) {
                $this->assertFalse($record['allowed_change']);
            }
        }
        $rules = json_encode($this->json('existing_target_insurance_rules.json'), JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES);
        $this->assertStringContainsString('pre-existing target patient remains evidence-only', $rules);
        $this->assertStringContainsString('no membership create/update/delete', $rules);
        $this->assertStringContainsString('idempotency failure', $rules);
    }

    #[Test]
    public function extraction_is_coordinated_and_repository_output_is_aggregate_only(): void
    {
        $records = array_column($this->json('insurance_extraction_strategies.json')['records'], null, 'id');
        $this->assertSame('INS_ID ASC', $records['INS-EXT-001']['order']);
        $this->assertSame('PATIENT-EXT-001', $records['INS-EXT-002']['upstream_strategy']);
        $this->assertTrue($records['INS-EXT-002']['separate_snapshot_forbidden']);
        foreach ($records as $record) {
            $this->assertSame('aggregate-only', $record['repository_output']);
            $this->assertMatchesRegularExpression('/^[a-z0-9-]+-v1$/', $record['query_contract_id']);
        }
        $this->assertStringContainsString('SHA-256', $this->json('insurance_extraction_strategies.json')['query_hash_rule']);
        $manifest = $this->json('insurance_extraction_strategies.json')['evidence_manifest'];
        $this->assertSame('legacy_uhms', $manifest['connection']);
        $this->assertSame('uuhms', $manifest['database']);
        $this->assertMatchesRegularExpression('/^[a-f0-9]{64}$/', $manifest['aggregate_result_hash_sha256']);
        foreach ($manifest['queries'] as $query) {
            $this->assertSame(hash('sha256', $query['normalized_sql']), $query['query_hash_sha256'], $query['id']);
        }
        $bundleCanonical = implode('|', [
            $manifest['manifest_version'], $manifest['database'], $manifest['schema_fingerprint'],
            $manifest['executed_at_utc_date'], $manifest['evaluation_timezone'],
            $manifest['aggregate_result_hash_sha256'],
        ]);
        $this->assertSame(hash('sha256', $bundleCanonical), $manifest['bundle_identity_sha256']);
    }

    #[Test]
    public function every_exception_reference_resolves(): void
    {
        $known = array_column($this->json('insurance_exception_codes.json')['records'], 'code');
        $references = [];
        foreach (glob($this->root('docs/legacy-migration/phase-2e/specifications/*.json')) as $file) {
            $this->collectExceptionReferences($this->decode($file), $references);
        }
        foreach (array_unique($references) as $reference) {
            $this->assertContains($reference, $known, $reference);
        }
    }

    #[Test]
    public function every_reconciliation_partition_is_disjoint_versioned_and_fail_closed(): void
    {
        $records = $this->json('insurance_reconciliation_contracts.json')['records'];
        $ids = array_column($records, 'id');
        foreach ([
            'INS-REC-001', 'INS-REC-002', 'INS-REC-003', 'INS-REC-004',
            'INS-REC-005-ISSUE', 'INS-REC-005-EXPIRY', 'INS-REC-006',
            'INS-REC-007-GROUP', 'INS-REC-007-ROW', 'INS-REC-008',
            'INS-REC-009-SCHEME', 'INS-REC-009-PLAN', 'INS-REC-010',
            'INS-REC-011', 'INS-REC-012', 'INS-REC-013', 'INS-REC-014', 'INS-REC-015',
        ] as $required) {
            $this->assertContains($required, $ids);
        }
        foreach ($records as $record) {
            $this->assertSame(count($record['buckets']), count(array_unique($record['buckets'])), $record['id']);
            $this->assertSame($record['buckets'], $record['precedence'], $record['id']);
            $this->assertContains('failed', $record['buckets'], $record['id']);
            $this->assertSame(0, $record['required_difference'], $record['id']);
            $this->assertSame(0, $record['tolerance'], $record['id']);
            $this->assertNotEmpty($record['equation'], $record['id']);
            $this->assertNotEmpty($record['snapshot_dependencies'], $record['id']);
            foreach ($record['required_zero'] ?? [] as $expected) {
                $this->assertSame(0, $expected, $record['id']);
            }
        }
    }

    #[Test]
    public function currentness_parent_chain_and_field_sentinels_preserve_upstream_safety(): void
    {
        $classes = array_column($this->json('insurance_membership_classes.json')['records'], null, 'value');
        $this->assertStringContainsString('none in Phase 2E', $classes['INSURANCE_DATE_UNKNOWN']['current_membership_outcome']);

        $privacy = json_encode($this->json('insurance_privacy_provenance_contract.json'), JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES);
        $this->assertStringContainsString('PATIENT-PRIV-009 / legacy-insurance-chain-v1', $privacy);
        $this->assertStringContainsString('secondary Phase 2E insurance-row correlation only', $privacy);

        $sentinels = array_column($this->json('insurance_sentinel_rules.json')['records'], null, 'field');
        $this->assertStringContainsString('Only blank', $sentinels['Plan']['rule']);
        $memberRecords = array_column($this->json('insurance_member_number_rules.json')['records'], null, 'id');
        $comparison = $memberRecords['INS-MEMBER-007']['comparison'];
        $this->assertStringContainsString('preserve and compare case and punctuation exactly', $comparison);
        $this->assertStringNotContainsString('case-fold', $comparison);
    }

    #[Test]
    public function claims_finance_runtime_and_raw_phi_are_outside_the_normative_mapping(): void
    {
        $mappings = $this->json('insurance_column_mappings.json')['records'];
        foreach ($mappings as $mapping) {
            $this->assertDoesNotMatchRegularExpression('/claim|invoice|payment|receivable|journal|accounting|verification/i', $mapping['source']);
        }

        $scope = (string) file_get_contents($this->root('docs/legacy-migration/phase-2e/PHASE_2E_SCOPE_BOUNDARY.md'));
        foreach (['Claims', 'invoices', 'payments', 'accounting', 'eligibility API', 'verification creation'] as $term) {
            $this->assertStringContainsStringIgnoringCase($term, $scope);
        }

        $forbiddenKeys = ['patient_name', 'raw_patient_id', 'raw_target_id', 'raw_member_number', 'member_number_value', 'policy_number_value', 'row_hmac'];
        foreach (glob($this->root('docs/legacy-migration/phase-2e/specifications/*.json')) as $file) {
            $keys = [];
            $this->collectKeys($this->decode($file), $keys);
            foreach ($forbiddenKeys as $key) {
                $this->assertNotContains($key, $keys, basename($file));
            }
            $text = (string) file_get_contents($file);
            $this->assertDoesNotMatchRegularExpression('/\\b(?:\\+233|0(?:2[034567]|5[03459]))\\d{7}\\b/', $text, basename($file));
        }
    }

    #[Test]
    public function required_upstream_contract_ids_exist_in_the_authoritative_packages(): void
    {
        $checks = [
            'docs/legacy-migration/phase-2a/specifications/reference_natural_keys.json' => 'NK-004',
            'docs/legacy-migration/phase-2a/specifications/reference_extraction_strategies.json' => 'EXTRACT-SETT_PRIVATE',
            'docs/legacy-migration/phase-2a/specifications/reference_reconciliation_contracts.json' => 'RECON-SETT_PRIVATE',
            'docs/legacy-migration/phase-2c/specifications/patient_relationship_rules.json' => 'PATIENT-REL-003',
            'docs/legacy-migration/phase-2c/specifications/patient_sentinel_rules.json' => 'PATIENT-SENT-003',
            'docs/legacy-migration/phase-2c/specifications/patient_privacy_provenance_contract.json' => 'PATIENT-PRIV-009',
            'docs/legacy-migration/phase-2d/specifications/patient_child_relationship_rules.json' => 'PATIENT-CHILD-REL-005',
        ];
        foreach ($checks as $file => $id) {
            $this->assertStringContainsString($id, (string) file_get_contents($this->root($file)), $id);
        }
    }

    #[Test]
    public function provider_projection_language_is_consistent_across_required_artifacts(): void
    {
        $required = [
            'PATIENT_INSURANCE_COLUMN_MAPPING.md', 'PATIENT_COMPANY_PAYER_EVIDENCE.md',
            'INSURANCE_PROVIDER_INTEGRATION_CONTRACT.md', 'INSURANCE_RELATIONSHIP_REGISTER.md',
        ];
        foreach ($required as $name) {
            $text = (string) file_get_contents($this->root('docs/legacy-migration/phase-2e/'.$name));
            $this->assertStringNotContainsString('NK-004 unique match', $text, $name);
            $this->assertStringNotContainsString('unique Phase 2A NK-004 match', $text, $name);
        }
        $company = json_encode($this->json('patient_company_rules.json'), JSON_THROW_ON_ERROR);
        $this->assertStringNotContainsString('unique Phase 2A NK-004 match', $company);
        $this->assertStringContainsString('INSURANCE-COMPANY-TO-PROVIDER-PROJECTION-V1', $company);
        $relationships = json_encode($this->json('insurance_relationship_rules.json'), JSON_THROW_ON_ERROR);
        $this->assertStringNotContainsString('Phase 2A NK-004 agreement', $relationships);
        $this->assertStringContainsString('INSURANCE-COMPANY-TO-PROVIDER-PROJECTION-V1', $relationships);
        $this->assertStringContainsString('PATIENT-PRIV-009', $relationships);
    }

    #[Test]
    public function all_phase_2e_artifacts_pass_value_free_privacy_patterns(): void
    {
        $files = array_merge(
            glob($this->root('docs/legacy-migration/phase-2e/*.md')) ?: [],
            glob($this->root('docs/legacy-migration/phase-2e/drafts/*.md')) ?: [],
            glob($this->root('docs/legacy-migration/phase-2e/specifications/*.json')) ?: [],
            glob($this->root('docs/legacy-migration/evidence/PHASE_2E_*.json')) ?: [],
        );
        $this->assertNotEmpty($files);
        foreach ($files as $file) {
            $text = (string) file_get_contents($file);
            $unsafe = preg_match('/\\b(?:\\+233|0(?:2[034567]|5[03459]))\\d{7}\\b|[A-Z0-9._%+-]+@[A-Z0-9.-]+\\.[A-Z]{2,}|MemberNo\\s*=\\s*[\"\'][^\"\']+/i', $text) === 1;
            $this->assertFalse($unsafe, basename($file));
        }
    }

    #[Test]
    public function phase_2e_classic_evidence_is_complete_hashed_and_balanced(): void
    {
        $queryPath = $this->root('docs/legacy-migration/evidence/PHASE_2E_CLASSIC_QUERY_MANIFEST.json');
        $resultPath = $this->root('docs/legacy-migration/evidence/PHASE_2E_CLASSIC_AGGREGATE_RESULTS.json');
        $queries = $this->decode($queryPath);
        $results = $this->decode($resultPath);

        $this->assertSame('legacy_uhms', $queries['approved_connection']);
        $this->assertSame('uuhms', $queries['approved_database']);
        $this->assertTrue($queries['schema_guard']['match']);
        $this->assertTrue($queries['snapshot']['session_transaction_read_only']);
        $this->assertSame('rolled_back', $queries['snapshot']['completion']);
        $this->assertSame(15, $queries['query_count']);
        $this->assertCount(15, $queries['queries']);
        $this->assertSame('complete_for_published_classic_aggregates', $results['status']);
        $this->assertSame(15, $results['record_count']);
        $this->assertCount(15, $results['records']);
        $this->assertSame(hash('sha256', $queries['tool_contract']), $queries['tool_contract_hash_sha256']);

        $resultByQuery = array_column($results['records'], null, 'query_id');
        foreach ($queries['queries'] as $query) {
            $this->assertSame(hash('sha256', $query['normalized_sql']), $query['query_hash_sha256'], $query['id']);
            $this->assertSame('completed', $query['status'], $query['id']);
            $this->assertArrayHasKey($query['id'], $resultByQuery, $query['id']);
            $this->assertSame($query['result_hash_sha256'], $resultByQuery[$query['id']]['result_hash_sha256'], $query['id']);
        }
        foreach ($results['records'] as $record) {
            $canonical = json_encode($record['result_contract'], JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES);
            $this->assertSame(hash('sha256', $canonical), $record['result_hash_sha256'], $record['id']);
            if (array_key_exists('difference', $record['result_contract'])) {
                $this->assertSame(0, $record['result_contract']['difference'], $record['id']);
            }
        }
        $payload = json_encode($results['records'], JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES);
        $this->assertSame(hash('sha256', $payload), $results['aggregate_payload_hash_sha256']);
        foreach ($results['required_zero_assertions'] as $name => $value) {
            $this->assertSame(0, $value, $name);
        }
    }

    #[Test]
    public function phase_2e_target_evidence_is_complete_read_only_suppression_safe_and_hashed(): void
    {
        $queryPath = $this->root('docs/legacy-migration/evidence/PHASE_2E_TARGET_QUERY_MANIFEST.json');
        $resultPath = $this->root('docs/legacy-migration/evidence/PHASE_2E_TARGET_AGGREGATE_RESULTS.json');
        $queries = $this->decode($queryPath);
        $results = $this->decode($resultPath);

        $this->assertSame('uhms_clean', $queries['database_guard']['observed_database']);
        $this->assertTrue($queries['database_guard']['session_transaction_read_only']);
        $this->assertSame('complete_read_only_suppression_safe', $results['capture_status']);
        $this->assertTrue($results['target']['session_transaction_read_only']);
        $this->assertSame('ROLLBACK', $results['target']['transaction_completion']);
        $this->assertTrue($results['target']['schema_fingerprint_match']);
        $this->assertCount(13, $queries['queries']);
        $this->assertCount(13, $results['results']);
        $this->assertSame(array_keys($results['results']), array_keys($results['result_hashes']));
        $this->assertSame('uhms_clean', $results['results']['guard']['database_name']);
        $this->assertTrue($results['results']['guard']['session_read_only']);
        $this->assertSame($results['captured_at_utc'], $results['results']['guard']['captured_at_utc']);
        foreach ($queries['queries'] as $query) {
            $this->assertSame(hash('sha256', $query['normalized_sql']), $query['query_sha256'], $query['query_id']);
            $segments = explode('.', $query['query_id']);
            $this->assertArrayHasKey(end($segments), $results['result_hashes'], $query['query_id']);
        }
        $this->assertSame(
            hash('sha256', $queries['implementation']['canonical_description']),
            $queries['implementation']['sha256'],
        );
        foreach ($results['results'] as $id => $result) {
            $canonical = json_encode($result, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            $preimage = "phase2e-public-result-v1\0{$id}\0{$canonical}";
            $this->assertSame(hash('sha256', $preimage), $results['result_hashes'][$id], $id);
        }
        foreach ($queries['privacy_assertions'] as $name => $value) {
            $this->assertSame(0, $value, $name);
        }
        foreach ($results['privacy_and_safety'] as $name => $value) {
            if (is_int($value)) {
                $this->assertSame(0, $value, $name);
            }
        }

        $queryHashes = [];
        foreach ($queries['queries'] as $query) {
            $segments = explode('.', $query['query_id']);
            $queryHashes[end($segments)] = $query['query_sha256'];
        }
        $contentPreimage = [
            'implementation_sha256' => $queries['implementation']['sha256'],
            'query_sha256' => $queryHashes,
            'result_sha256' => $results['result_hashes'],
            'target_fingerprint' => $queries['target_fingerprint_contract']['observed'],
        ];
        $canonicalContent = json_encode(
            $this->canonicalize($contentPreimage),
            JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE,
        );
        $contentHash = hash('sha256', $canonicalContent);
        $this->assertSame($contentHash, $queries['bundle_identity']['content_sha256']);
        $this->assertSame($contentHash, $results['bundle_identity']['content_sha256']);
        $captureHash = hash('sha256', $contentHash."\0".$results['captured_at_utc']);
        $this->assertSame($captureHash, $queries['bundle_identity']['capture_instance_sha256']);
        $this->assertSame($captureHash, $results['bundle_identity']['capture_instance_sha256']);
    }

    private function canonicalize(mixed $value): mixed
    {
        if (!is_array($value)) {
            return $value;
        }
        if (!array_is_list($value)) {
            ksort($value, SORT_STRING);
        }
        foreach ($value as $key => $item) {
            $value[$key] = $this->canonicalize($item);
        }

        return $value;
    }

    /** @param array<string, mixed> $value @param list<string> $references */
    private function collectExceptionReferences(array $value, array &$references): void
    {
        foreach ($value as $key => $item) {
            if (str_ends_with($key, 'exception_code') && is_string($item)) {
                $references[] = $item;
            } elseif (is_array($item)) {
                $this->collectExceptionReferences($item, $references);
            }
        }
    }

    /** @param array<string, mixed> $value @param list<string> $keys */
    private function collectKeys(array $value, array &$keys): void
    {
        foreach ($value as $key => $item) {
            if (is_string($key)) {
                $keys[] = $key;
            }
            if (is_array($item)) {
                $this->collectKeys($item, $keys);
            }
        }
    }

    /** @return array<string, mixed> */
    private function json(string $name): array
    {
        return $this->decode($this->root('docs/legacy-migration/phase-2e/specifications/'.$name));
    }

    /** @return array<string, mixed> */
    private function decode(string $path): array
    {
        return json_decode((string) file_get_contents($path), true, flags: JSON_THROW_ON_ERROR);
    }

    private function root(string $relative): string
    {
        return dirname(__DIR__, 4).DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $relative);
    }
}
