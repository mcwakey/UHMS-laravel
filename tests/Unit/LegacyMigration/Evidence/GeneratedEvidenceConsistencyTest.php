<?php

namespace Tests\Unit\LegacyMigration\Evidence;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class GeneratedEvidenceConsistencyTest extends TestCase
{
    #[Test]
    public function classic_manifests_are_cross_linked_and_internally_consistent(): void
    {
        $schema = $this->json('docs/legacy-migration/evidence/CLASSIC_SCHEMA_MANIFEST.json');
        $relationships = $this->json('docs/legacy-migration/evidence/CLASSIC_RELATIONSHIP_MANIFEST.json');
        $queries = $this->json('docs/legacy-migration/evidence/CLASSIC_QUERY_MANIFEST.json');
        $queryIds = array_column($queries['queries'], 'id');

        $this->assertSame('uuhms', $schema['approved_database']);
        $this->assertSame(53, $schema['constraint_evidence']['primary_key_index_count']);
        $this->assertSame(0, $schema['constraint_evidence']['verified_foreign_key_count']);
        $this->assertSame(0, $schema['constraint_evidence']['non_primary_unique_index_count']);
        $this->assertCount(count(array_unique($queryIds)), $queryIds);
        $this->assertCount(77, $relationships['relationships']);

        foreach ($relationships['relationships'] as $relationship) {
            $counts = $relationship['counts'];
            $this->assertSame(
                $counts['child_rows'],
                $counts['null_count'] + $counts['candidate_sentinel_count'] + $counts['matched_non_sentinel_count'] + $counts['orphan_excluding_candidate_sentinel_count'],
            );
            $this->assertContains($relationship['evidence_query']['query_id'], $queryIds);
        }
    }

    #[Test]
    public function unapproved_categorical_literals_are_collapsed_without_per_value_hashes(): void
    {
        $aggregates = $this->json('docs/legacy-migration/evidence/CLASSIC_AGGREGATE_RESULTS.json');
        $redactedCount = 0;

        foreach ($aggregates['categorical_status_counts'] as $values) {
            foreach ($values as $value) {
                if (! $value['redacted']) {
                    continue;
                }

                $redactedCount++;
                $this->assertSame('__REDACTED_UNAPPROVED_LITERAL__', $value['value']);
                $this->assertArrayNotHasKey('value_sha256', $value);
                $this->assertGreaterThan(0, $value['distinct_value_count']);
            }
        }

        $this->assertGreaterThan(0, $redactedCount);
    }

    #[Test]
    public function classic_temporal_extrema_are_redacted_from_repository_evidence(): void
    {
        $aggregates = $this->json('docs/legacy-migration/evidence/CLASSIC_AGGREGATE_RESULTS.json');

        $this->assertFalse($aggregates['privacy']['patient_derived_temporal_extrema_included']);
        $this->assertSame('phase-2c-temporal-extrema-redaction/1.0.0', $aggregates['repository_sanitization']['policy_version']);

        $redacted = 0;
        foreach ($aggregates['temporal_column_profiles'] as $profile) {
            $this->assertNull($profile['results']['valid_min']);
            $this->assertNull($profile['results']['valid_max']);
            $redacted += 2;
        }

        $this->assertSame($aggregates['repository_sanitization']['redacted_field_count'], $redacted);
    }

    #[Test]
    public function historical_temporal_result_hashes_cannot_confirm_redacted_extrema(): void
    {
        $queries = $this->json('docs/legacy-migration/evidence/CLASSIC_QUERY_MANIFEST.json');
        $historicalTemporalQueries = 0;

        foreach ($queries['queries'] as $query) {
            if (! str_starts_with($query['id'], 'classic.temporal_profile.')) {
                continue;
            }

            $containsExtrema = str_contains($query['normalized_sql'], ' AS valid_min')
                || str_contains($query['normalized_sql'], ' AS valid_max');

            if ($containsExtrema) {
                $historicalTemporalQueries++;
                $this->assertNull($query['result_hash_sha256']);
            }
        }

        $this->assertSame($queries['repository_sanitization']['redacted_result_hash_count'], $historicalTemporalQueries);
    }

    #[Test]
    public function target_manifest_contains_database_executable_object_catalogues(): void
    {
        $target = $this->json('docs/legacy-migration/TARGET_CONSTRAINT_MANIFEST.json');

        $this->assertSame('uhms_clean', $target['database']['name']);
        $this->assertArrayHasKey('triggers', $target);
        $this->assertArrayHasKey('events', $target);
        $this->assertArrayHasKey('routines', $target);
    }

    /** @return array<string, mixed> */
    private function json(string $relativePath): array
    {
        $path = dirname(__DIR__, 4).DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $relativePath);

        return json_decode((string) file_get_contents($path), true, flags: JSON_THROW_ON_ERROR);
    }
}
