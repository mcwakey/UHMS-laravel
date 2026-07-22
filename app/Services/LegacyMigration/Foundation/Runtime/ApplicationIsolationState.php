<?php

namespace App\Services\LegacyMigration\Foundation\Runtime;

final class ApplicationIsolationState
{
    /** @var array<string,true> */
    private array $isolated = [];

    /** @var array<string,ProhibitedSubsystem> */
    private array $resolutionBindings = [];

    public function __construct(private readonly SideEffectCounter $counter) {}

    public function activate(ProhibitedSubsystem $subsystem): void
    {
        $this->isolated[$subsystem->value] = true;
    }

    public function deactivate(ProhibitedSubsystem $subsystem): void
    {
        unset($this->isolated[$subsystem->value]);
    }

    public function isolated(ProhibitedSubsystem $subsystem): bool
    {
        return isset($this->isolated[$subsystem->value]);
    }

    public function registerResolutionBinding(string $abstract, ProhibitedSubsystem $subsystem): void
    {
        if ($abstract === '') {
            throw new RuntimeIsolationException('FOUNDATION_ISOLATION_BINDING_INVALID', 'An application isolation binding is invalid.');
        }
        $this->resolutionBindings[$abstract] = $subsystem;
    }

    public function assertResolutionAllowed(string $abstract): void
    {
        $subsystem = $this->resolutionBindings[$abstract] ?? null;
        if ($subsystem === null || ! $this->isolated($subsystem)) {
            return;
        }
        $this->counter->record($subsystem);
        throw new RuntimeIsolationException('FOUNDATION_APPLICATION_EFFECT_RESOLUTION_DENIED', 'A prohibited application effect binding was denied.');
    }

    /** Invocation-time guard for operational objects resolved before isolation. */
    public function assertInvocationAllowed(ProhibitedSubsystem $subsystem): void
    {
        if ($this->isolated($subsystem)) {
            $this->deny($subsystem);
        }
    }

    public function deny(ProhibitedSubsystem $subsystem): never
    {
        $this->counter->record($subsystem);
        throw new RuntimeIsolationException('FOUNDATION_APPLICATION_EFFECT_DENIED', 'A prohibited application effect was denied.');
    }
}
