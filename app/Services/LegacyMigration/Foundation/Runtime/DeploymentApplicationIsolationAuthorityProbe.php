<?php

namespace App\Services\LegacyMigration\Foundation\Runtime;

final readonly class DeploymentApplicationIsolationAuthorityProbe implements ApplicationIsolationAuthorityProbe
{
    /** @param array<string,mixed> $configuration */
    public function __construct(private ApplicationIsolationBarrierProvider $provider, private array $configuration) {}

    public function observe(): ApplicationIsolationAuthorityEvidence
    {
        $bindings = (array) ($this->configuration['isolation']['application_bindings'] ?? []);
        $expected = [];
        foreach (['authority_reference', 'queue_worker_barrier_reference', 'scheduler_barrier_reference', 'null_sink_reference'] as $field) {
            $value = $bindings[$field] ?? null;
            if (! is_string($value) || trim($value) === '') {
                throw new RuntimeIsolationException('FOUNDATION_ISOLATION_BOOT_REFERENCE_MISSING', 'An application isolation boot reference is missing.');
            }
            $expected[$field] = $value;
        }
        $observed = $this->provider->observe($expected);
        if (! hash_equals($expected['authority_reference'], $observed->authorityReference)
            || ! hash_equals($expected['queue_worker_barrier_reference'], $observed->queueWorkerBarrierReference)
            || ! hash_equals($expected['scheduler_barrier_reference'], $observed->schedulerBarrierReference)
            || ! hash_equals($expected['null_sink_reference'], $observed->nullSinkReference)
            || $observed->observationReference === '') {
            throw new RuntimeIsolationException('FOUNDATION_ISOLATION_BARRIER_OBSERVATION_MISMATCH', 'Observed deployment barriers do not match the pinned references.');
        }

        return $observed;
    }
}
