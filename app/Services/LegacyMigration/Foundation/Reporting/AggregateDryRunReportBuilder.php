<?php

namespace App\Services\LegacyMigration\Foundation\Reporting;

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
