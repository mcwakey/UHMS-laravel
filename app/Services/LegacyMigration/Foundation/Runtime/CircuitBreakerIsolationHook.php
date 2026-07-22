<?php

namespace App\Services\LegacyMigration\Foundation\Runtime;

final class CircuitBreakerIsolationHook implements SubsystemIsolationHook
{
    public function __construct(
        private readonly ApplicationIsolationState $state,
        private readonly ProhibitedSubsystem $subsystem,
        private readonly string $bindingProofReference,
    ) {
        if ($bindingProofReference === '') {
            throw new RuntimeIsolationException('FOUNDATION_ISOLATION_BINDING_PROOF_MISSING', 'An application isolation binding proof is missing.');
        }
    }

    public function proofReference(): string
    {
        return $this->bindingProofReference;
    }

    public function capture(): bool
    {
        return $this->state->isolated($this->subsystem);
    }

    public function isolate(): void
    {
        $this->state->activate($this->subsystem);
    }

    public function isolated(): bool
    {
        return $this->state->isolated($this->subsystem);
    }

    public function restore(mixed $state): void
    {
        $state === true ? $this->state->activate($this->subsystem) : $this->state->deactivate($this->subsystem);
    }
}
