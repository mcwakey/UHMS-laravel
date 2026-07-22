<?php

namespace App\Services\LegacyMigration\Foundation\Reporting;

use App\Services\LegacyMigration\Foundation\Reconciliation\AuthoritativeDryRunResult;
use InvalidArgumentException;

final class AggregateDryRunReportBuilder
{
    private const FORBIDDEN_KEY_FRAGMENTS = [
        'name', 'phone', 'address', 'member_number', 'insurance_number',
        'patient_number', 'opd', 'source_id', 'target_id', 'raw_value',
        'clinical_text', 'password', 'credential', 'secret', 'token_value',
    ];

    /**
     * @param  array<string, int|float|string|bool|null|array>  $aggregates
     * @return array<string, mixed>
     */
    public function build(string $runToken, string $snapshotToken, array $aggregates): array
    {
        $this->assertOpaqueToken($runToken, 'run');
        $this->assertOpaqueToken($snapshotToken, 'snapshot');
        $this->assertAggregatePayload($aggregates);

        return [
            'report_version' => 'phase-3/1.0.0',
            'classification' => 'aggregate_nonbinding_dry_run',
            'run_token' => $runToken,
            'snapshot_token' => $snapshotToken,
            'commit_authorized' => false,
            'business_domain_write_count' => 0,
            'source_write_count' => 0,
            'aggregates' => $aggregates,
        ];
    }

    /** @return array<string,mixed> */
    public function renderAuthoritative(AuthoritativeDryRunResult $result): array
    {
        $this->assertAuthoritativeResult($result);
        $sourceWrites = (int) $result->measurements['PILOT-DRY-008:source_writes']['observed_value'];
        $targetWrites = (int) $result->measurements['PILOT-DRY-008:target_writes']['observed_value'];

        return [
            'report_version' => 'phase-3b/1.0.0',
            'classification' => 'authoritative_aggregate_dry_run',
            'verdict' => $result->verdict,
            'run_token' => $result->runToken,
            'source_snapshot_id' => $result->sourceSnapshotId,
            'target_snapshot_id' => $result->targetSnapshotId,
            'contract_bundle_hash' => $result->bundleHash,
            'commit_authorized' => false,
            'source_write_count' => $sourceWrites,
            'target_write_count' => $targetWrites,
            'business_domain_write_count' => $targetWrites,
            'missing_measurement_count' => count($result->missing),
            'failed_measurement_count' => count($result->failed),
            'measurements' => $result->measurements,
        ];
    }

    private function assertAuthoritativeResult(AuthoritativeDryRunResult $result): void
    {
        if (! in_array($result->verdict, ['FAIL_CLOSED', 'BLOCKED_PREREQUISITE', 'DRY_RUN_ACCEPTED_NOT_COMMIT_AUTHORIZED'], true)
            || $result->commitAuthorized()
            || count($result->measurements) !== 476) {
            throw new InvalidArgumentException('Authoritative aggregate result is incomplete.');
        }
        foreach ([$result->bundleHash, $result->runToken, $result->sourceSnapshotId, $result->targetSnapshotId] as $digest) {
            if (preg_match('/\A[a-f0-9]{64}\z/', $digest) !== 1) {
                throw new InvalidArgumentException('Authoritative aggregate coordinates are invalid.');
            }
        }
        foreach (['PILOT-DRY-008:source_writes', 'PILOT-DRY-008:target_writes'] as $id) {
            $measurement = $result->measurements[$id] ?? null;
            if (! is_array($measurement)
                || preg_match('/\A[0-9]+\z/', (string) ($measurement['observed_value'] ?? '')) !== 1) {
                throw new InvalidArgumentException('Observed write counters are unavailable.');
            }
        }
        $allowedEquations = [
            'verified_contract_present', 'protected_input_baseline_observed',
            'source_cohort_coordinate_observed', 'sealed_input_coordinate_observed',
            'observed_counter_must_equal_zero', 'classified_outcome_count_is_nonnegative',
            'recorded_population_minus_recorded_terminal_outcomes_equals_zero', 'mandatory',
        ];
        $allowedClassifications = [
            'verified_bundle_contract', 'recorder_derived_input', 'recorder_derived_safety_counter',
            'recorder_derived_classified_outcome', 'not_applicable_with_rule_empty_cohort',
            'recorder_derived_accounting', 'blocked_not_measured',
        ];
        foreach ($result->measurements as $id => $measurement) {
            if (preg_match('/\A[A-Z0-9_-]+:[a-z0-9_]+\z/', (string) $id) !== 1
                || ! is_array($measurement)
                || preg_match('/\A[A-Z0-9_-]+\z/', (string) ($measurement['contract_id'] ?? '')) !== 1
                || ! in_array($measurement['expected_equation'] ?? null, $allowedEquations, true)
                || ! in_array($measurement['classification'] ?? null, $allowedClassifications, true)
                || preg_match('/\A[+-]?[0-9]+(?:\.[0-9]+)?\z/', (string) ($measurement['observed_value'] ?? '')) !== 1
                || preg_match('/\A[+-]?[0-9]+(?:\.[0-9]+)?\z/', (string) ($measurement['difference'] ?? '')) !== 1
                || preg_match('/\A[+-]?[0-9]+(?:\.[0-9]+)?\z/', (string) ($measurement['tolerance'] ?? '')) !== 1) {
                throw new InvalidArgumentException('Authoritative report contains non-aggregate measurement material.');
            }
        }
    }

    private function assertOpaqueToken(string $token, string $kind): void
    {
        if (preg_match('/^[a-z0-9_-]{12,191}$/iD', $token) !== 1) {
            throw new InvalidArgumentException("The {$kind} reference must be an opaque token.");
        }
    }

    private function assertAggregatePayload(array $payload, string $path = 'aggregates'): void
    {
        foreach ($payload as $key => $value) {
            $normalized = strtolower((string) $key);
            foreach (self::FORBIDDEN_KEY_FRAGMENTS as $fragment) {
                if (str_contains($normalized, $fragment)) {
                    throw new InvalidArgumentException("Record-level field is forbidden in aggregate report at {$path}.");
                }
            }

            if (is_array($value)) {
                $this->assertAggregatePayload($value, $path.'.'.$normalized);

                continue;
            }

            if (! is_int($value) && ! is_float($value) && ! is_bool($value) && $value !== null) {
                throw new InvalidArgumentException("Only numeric, boolean or null aggregate values are permitted at {$path}.");
            }
        }
    }
}
