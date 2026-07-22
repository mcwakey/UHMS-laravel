<?php

namespace App\Services\LegacyMigration\Foundation\Runtime;

use Illuminate\Support\Facades\Facade;

final class FacadeSinkIsolationHook implements SubsystemIsolationHook
{
    private ?object $sink = null;

    public function __construct(
        private readonly string $facade,
        private readonly ApplicationIsolationState $state,
        private readonly ProhibitedSubsystem $subsystem,
        private readonly string $bindingProofReference,
    ) {
        if (! is_subclass_of($facade, Facade::class) || $bindingProofReference === '') {
            throw new RuntimeIsolationException('FOUNDATION_FACADE_ISOLATION_INVALID', 'A facade isolation binding is invalid.');
        }
    }

    public function proofReference(): string
    {
        return $this->bindingProofReference;
    }

    public function capture(): mixed
    {
        return $this->facade::getFacadeRoot();
    }

    public function isolate(): void
    {
        $this->sink = new DenyingFacadeSink($this->state, $this->subsystem);
        $this->facade::swap($this->sink);
        $this->state->activate($this->subsystem);
    }

    public function isolated(): bool
    {
        return $this->sink !== null && $this->facade::getFacadeRoot() === $this->sink && $this->state->isolated($this->subsystem);
    }

    public function restore(mixed $state): void
    {
        $this->facade::swap($state);
        $this->sink = null;
        $this->state->deactivate($this->subsystem);
    }
}

final class DenyingFacadeSink
{
    public function __construct(private readonly ApplicationIsolationState $state, private readonly ProhibitedSubsystem $subsystem) {}

    public function __call(string $method, array $arguments): never
    {
        $this->state->deny($this->subsystem);
    }
}
