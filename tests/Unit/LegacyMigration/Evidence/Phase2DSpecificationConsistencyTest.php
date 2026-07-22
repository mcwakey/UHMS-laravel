<?php

namespace Tests\Unit\LegacyMigration\Evidence;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
final class Phase2DSpecificationConsistencyTest extends TestCase
{
    private const SOURCE_COLUMNS = [
        'Work', 'Address', 'NOK', 'NOKPhoneNo', 'NOKRel', 'Religion', 'MaritalStatus',
    ];

    #[Test]
    public function required_specs_parse_and_are_non_executable_uuhms_contracts(): void
    {
        $files = glob($this->root('docs/legacy-migration/phase-2d/specifications/*.json'));
        $required = [
            'address_rules.json', 'alias_child_integration_rules.json', 'emergency_contact_rules.json',
            'existing_target_child_immutability_rules.json', 'marital_status_value_crosswalks.json',
            'nok_tuple_rules.json', 'occupation_rules.json', 'patient_child_classes.json',
            'patient_child_column_mappings.json', 'patient_child_exception_codes.json',
            'patient_child_extraction_strategies.json', 'patient_child_privacy_provenance_contract.json',
            'patient_child_reconciliation_contracts.json', 'patient_child_relationship_rules.json',
            'patient_child_sentinel_rules.json', 'religion_value_crosswalks.json',
        ];

        $this->assertIsArray($files);
        $this->assertCount(16, $files);
        $actual = array_map('basename', $files);
        sort($actual);
        sort($required);
        $this->assertSame($required, $actual);

        foreach ($files as $file) {
            $spec = $this->decode($file);
            $this->assertSame('2D.1.0', $spec['specification_version'], basename($file));
            $this->assertSame('uuhms', $spec['approved_source']['database'], basename($file));
            $this->assertFalse($spec['implementation_authorized'], basename($file));
            $this->assertSame($spec['record_count'], count($spec['records']), basename($file));
        }
    }

    #[Test]
    public function exactly_the_seven_deferred_columns_are_classified_once(): void
    {
        $spec = $this->json('patient_child_column_mappings.json');
        $columns = array_column($spec['records'], 'source_column');

        $this->assertSame(self::SOURCE_COLUMNS, $columns);
        $this->assertCount(count(array_unique($columns)), $columns);
        $this->assertSame(['Company', 'BillStatus'], $spec['explicit_exclusions']['phase_2e']);
        $this->assertSame(['Allergies', 'Medication', 'History'], $spec['explicit_exclusions']['clinical']);
    }

    #[Test]
    public function alias_integration_delegates_to_phase_2c_without_an_alternate_canonicalizer(): void
    {
        $spec = $this->json('alias_child_integration_rules.json');

        $this->assertSame('LEGACY_OPD_ALIAS_CONTRACT', $spec['authoritative_upstream_contract']);
        $this->assertSame('PATIENT-ALIAS-003', $spec['canonicalization_contract']);
        $this->assertSame('PATIENT-REC-ALIAS-002', $spec['records'][9]['reconciliation_id']);
        $this->assertStringContainsString('zero alias rows', strtolower($spec['records'][9]['rule']));
    }

    #[Test]
    public function every_relationship_has_exactly_one_resolving_sentinel(): void
    {
        $relationships = $this->json('patient_child_relationship_rules.json')['records'];
        $sentinels = $this->json('patient_child_sentinel_rules.json')['records'];
        $sentinelsByRelationship = [];

        foreach ($sentinels as $sentinel) {
            $sentinelsByRelationship[$sentinel['relationship_id']][] = $sentinel['id'];
        }

        $this->assertCount(5, $relationships);
        foreach ($relationships as $relationship) {
            $this->assertSame([$relationship['sentinel_rule_id']], $sentinelsByRelationship[$relationship['id']] ?? []);
        }
    }

    #[Test]
    public function tuple_and_each_demographic_have_zero_difference_partitions(): void
    {
        $records = $this->json('patient_child_reconciliation_contracts.json')['records'];
        $byId = array_column($records, null, 'id');

        foreach (['PATIENT-CHILD-REC-001', 'PATIENT-CHILD-REC-003', 'PATIENT-CHILD-REC-004', 'PATIENT-CHILD-REC-005', 'PATIENT-CHILD-REC-006'] as $id) {
            $this->assertSame(0, $byId[$id]['required_difference']);
            $this->assertSame('population = sum(buckets)', $byId[$id]['equation']);
        }
    }

    #[Test]
    public function existing_target_and_privacy_assertions_are_fail_closed(): void
    {
        $immutability = $this->json('existing_target_child_immutability_rules.json');
        foreach (array_slice($immutability['records'], 0, 10) as $record) {
            $this->assertFalse($record['allowed_change']);
        }

        $privacy = $this->json('patient_child_privacy_provenance_contract.json');
        $this->assertFalse($privacy['privacy']['contains_raw_phi']);
        $this->assertSame('PATIENT-CHILD-REC-009', $privacy['required_zero_assertions']);
    }

    #[Test]
    public function every_phase_2d_exception_reference_resolves(): void
    {
        $known = array_column($this->json('patient_child_exception_codes.json')['records'], 'code');
        $references = [];

        foreach (glob($this->root('docs/legacy-migration/phase-2d/specifications/*.json')) as $file) {
            $this->collectExceptionReferences($this->decode($file), $references);
        }

        foreach (array_unique($references) as $reference) {
            $this->assertContains($reference, $known, $reference);
        }
    }

    #[Test]
    public function every_field_and_tuple_outcome_has_stable_exception_coverage(): void
    {
        $requiredBySpec = [
            'nok_tuple_rules.json' => [
                'LEGACY-PATIENT-CHILD-CONTACT-001', 'LEGACY-PATIENT-CHILD-CONTACT-002',
                'LEGACY-PATIENT-CHILD-CONTACT-003', 'LEGACY-PATIENT-CHILD-CONTACT-004',
                'LEGACY-PATIENT-CHILD-CONTACT-005', 'LEGACY-PATIENT-CHILD-CONTACT-006',
                'LEGACY-PATIENT-CHILD-CONTACT-007', 'LEGACY-PATIENT-CHILD-CONTACT-008',
                'LEGACY-PATIENT-CHILD-CONTACT-009', 'LEGACY-PATIENT-CHILD-CONTACT-010',
                'LEGACY-PATIENT-CHILD-CONTACT-011', 'LEGACY-PATIENT-CHILD-PARENT-001',
                'LEGACY-PATIENT-CHILD-PARENT-002', 'LEGACY-PATIENT-CHILD-EXTRACT-033',
            ],
            'occupation_rules.json' => [
                'LEGACY-PATIENT-CHILD-OCC-015', 'LEGACY-PATIENT-CHILD-OCC-016',
                'LEGACY-PATIENT-CHILD-OCC-017', 'LEGACY-PATIENT-CHILD-PARENT-001',
                'LEGACY-PATIENT-CHILD-EXTRACT-033', 'LEGACY-PATIENT-CHILD-TARGET-036',
            ],
            'address_rules.json' => [
                'LEGACY-PATIENT-CHILD-ADDR-018', 'LEGACY-PATIENT-CHILD-ADDR-019',
                'LEGACY-PATIENT-CHILD-ADDR-020', 'LEGACY-PATIENT-CHILD-PARENT-001',
                'LEGACY-PATIENT-CHILD-EXTRACT-033', 'LEGACY-PATIENT-CHILD-TARGET-036',
            ],
            'religion_value_crosswalks.json' => [
                'LEGACY-PATIENT-CHILD-RELIGION-021', 'LEGACY-PATIENT-CHILD-RELIGION-022',
                'LEGACY-PATIENT-CHILD-RELIGION-023', 'LEGACY-PATIENT-CHILD-RELIGION-024',
                'LEGACY-PATIENT-CHILD-PARENT-001', 'LEGACY-PATIENT-CHILD-EXTRACT-033',
                'LEGACY-PATIENT-CHILD-TARGET-036',
            ],
            'marital_status_value_crosswalks.json' => [
                'LEGACY-PATIENT-CHILD-MARITAL-025', 'LEGACY-PATIENT-CHILD-MARITAL-026',
                'LEGACY-PATIENT-CHILD-MARITAL-027', 'LEGACY-PATIENT-CHILD-TARGET-028',
                'LEGACY-PATIENT-CHILD-PARENT-001', 'LEGACY-PATIENT-CHILD-EXTRACT-033',
                'LEGACY-PATIENT-CHILD-TARGET-036',
            ],
        ];

        foreach ($requiredBySpec as $name => $required) {
            $references = [];
            $this->collectExceptionReferences($this->json($name), $references);
            foreach ($required as $code) {
                $this->assertContains($code, $references, $name.' lacks '.$code);
            }
        }
    }

    #[Test]
    public function extraction_is_the_same_phase_2c_snapshot_projection(): void
    {
        $record = $this->json('patient_child_extraction_strategies.json')['records'][0];

        $this->assertSame('PATIENT-EXT-001', $record['upstream_strategy']);
        $this->assertSame('PAT_ID ASC', $record['order']);
        $this->assertSame(['PAT_ID', ...self::SOURCE_COLUMNS], $record['selected_columns']);
        $this->assertSame('aggregate-only', $record['repository_output']);
        $this->assertMatchesRegularExpression('/^[a-f0-9]{64}$/', $record['query_hash_sha256']);
    }

    /** @param array<string, mixed> $value @param list<string> $references */
    private function collectExceptionReferences(array $value, array &$references): void
    {
        foreach ($value as $key => $item) {
            if (str_ends_with($key, 'exception_code') && is_string($item)) {
                $references[] = $item;
            } elseif ((str_ends_with($key, 'exception_codes') || str_ends_with($key, 'outcome_codes')) && is_array($item)) {
                foreach ($item as $code) {
                    if (is_string($code)) {
                        $references[] = $code;
                    }
                }
            } elseif (is_array($item)) {
                $this->collectExceptionReferences($item, $references);
            }
        }
    }

    /** @return array<string, mixed> */
    private function json(string $name): array
    {
        return $this->decode($this->root('docs/legacy-migration/phase-2d/specifications/'.$name));
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
