<?php

namespace App\Services\LegacyMigration\Foundation\Allocation;

final readonly class DeterministicPatientNumberAllocator implements MigrationPatientNumberAllocator
{
    public function __construct(
        private AllocationLineageResolver $lineage,
        private NumberReservationStore $reservations,
        private PatientNumberCollisionProbe $collisions,
    ) {}

    public function allocate(AllocationRequest $request): AllocationResult
    {
        if (($existing = $this->lineage->resolveSuccessful($request)) !== null) {
            $existing->assertCompatible($request);

            return AllocationResult::reused($existing);
        }

        if (($existing = $this->reservations->find($request)) !== null) {
            $existing->assertCompatible($request);

            return AllocationResult::reused($existing);
        }

        if ($request->mode === AllocationMode::DryRun) {
            return AllocationResult::symbolic($request->configuration);
        }

        return $this->reservations->withLockedCoordinate(
            $request->configuration,
            function () use ($request): AllocationResult {
                // Recheck both authoritative lineage stores after taking the sequence lock.
                $existing = $this->lineage->resolveSuccessful($request)
                    ?? $this->reservations->find($request);
                if ($existing !== null) {
                    $existing->assertCompatible($request);

                    return AllocationResult::reused($existing);
                }

                $ordinal = $this->reservations->nextOrdinal($request->configuration);
                $number = $request->configuration->format($ordinal);
                if ($this->collisions->status($number) !== CollisionStatus::Available) {
                    throw AllocationException::failClosed('LEGACY-PATIENT-NUMBER-037');
                }

                $reserved = $this->reservations->persist($request, $number, $ordinal);
                $reserved->assertCompatible($request);

                return AllocationResult::reserved($reserved->number, $reserved->sequenceOrdinal, $request->configuration);
            },
        );
    }
}
