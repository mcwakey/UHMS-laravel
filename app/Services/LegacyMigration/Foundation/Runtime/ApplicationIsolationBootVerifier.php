<?php

namespace App\Services\LegacyMigration\Foundation\Runtime;

final class ApplicationIsolationBootVerifier
{
    public function __construct(
        private readonly ApplicationIsolationAuthorityProbe $authority = new MissingApplicationIsolationAuthorityProbe,
    ) {}

    /** @param iterable<SubsystemIsolationControl> $controls @param array<string,mixed> $configuration */
    public function verify(iterable $controls, array $configuration): void
    {
        $runtime = (array) ($configuration['isolation']['application_bindings'] ?? []);
        foreach (['bindings_verified', 'queue_workers_paused', 'scheduler_paused', 'external_integrations_sink_verified'] as $flag) {
            if (($runtime[$flag] ?? false) !== true) {
                throw new RuntimeIsolationException('FOUNDATION_ISOLATION_BOOT_PREREQUISITE_MISSING', 'An application isolation boot prerequisite is missing.');
            }
        }
        foreach (['authority_reference', 'queue_worker_barrier_reference', 'scheduler_barrier_reference', 'null_sink_reference'] as $reference) {
            if (! is_string($runtime[$reference] ?? null) || trim($runtime[$reference]) === '') {
                throw new RuntimeIsolationException('FOUNDATION_ISOLATION_BOOT_REFERENCE_MISSING', 'An application isolation boot reference is missing.');
            }
        }

        $observed = $this->authority->observe();
        if (! $observed->bindingsVerified || ! $observed->queueWorkersPaused || ! $observed->schedulerPaused || ! $observed->externalIntegrationsSinkVerified
            || $observed->observationReference === ''
            || ! hash_equals((string) $runtime['authority_reference'], $observed->authorityReference)
            || ! hash_equals((string) $runtime['queue_worker_barrier_reference'], $observed->queueWorkerBarrierReference)
            || ! hash_equals((string) $runtime['scheduler_barrier_reference'], $observed->schedulerBarrierReference)
            || ! hash_equals((string) $runtime['null_sink_reference'], $observed->nullSinkReference)) {
            throw new RuntimeIsolationException('FOUNDATION_ISOLATION_BOOT_PREREQUISITE_MISSING', 'Authoritatively observed application isolation does not match the approved references.');
        }

        $seen = [];
        foreach ($controls as $control) {
            if (! $control instanceof ApplicationBoundSubsystemControl || $control->proofReference() === '') {
                throw new RuntimeIsolationException('FOUNDATION_ISOLATION_BOOT_CONTROL_UNPROVEN', 'An application-bound isolation control is unproved.');
            }
            $seen[$control->subsystem()->value] = true;
        }
        foreach (ProhibitedSubsystem::cases() as $required) {
            if (! isset($seen[$required->value])) {
                throw new RuntimeIsolationException('FOUNDATION_ISOLATION_BOOT_CONTROL_MISSING', 'An application-bound isolation control is missing.');
            }
        }

    }
}
