<?php

namespace App\Services\LegacyMigration\Foundation\Allocation;

final readonly class LaravelTargetPatientNumberCollisionProbe implements PatientNumberCollisionProbe
{
    private PatientNumberCollisionNamespace $namespace;

    public function __construct(
        private PinnedCollisionEvidence $evidence,
        ?string $connection = null,
        ?PatientNumberCollisionNamespace $namespace = null,
    ) {
        $this->namespace = $namespace ?? new LaravelOperationalPatientNumberNamespace($connection);
    }

    public function status(string $candidate): CollisionStatus
    {
        if ($candidate === '' || ! $this->evidence->isCurrent()) {
            return CollisionStatus::Unknown;
        }

        try {
            if (! $this->namespace->available()) {
                return CollisionStatus::Unknown;
            }

            return $this->namespace->collides($candidate) ? CollisionStatus::Collision : CollisionStatus::Available;
        } catch (\Throwable) {
            return CollisionStatus::Unknown;
        }
    }
}
