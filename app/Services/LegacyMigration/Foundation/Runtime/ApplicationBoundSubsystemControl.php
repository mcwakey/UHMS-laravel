<?php

namespace App\Services\LegacyMigration\Foundation\Runtime;

final class ApplicationBoundSubsystemControl implements SubsystemIsolationControl
{
    private mixed $captured = null;

    private bool $capturedSet = false;

    public function __construct(private readonly ProhibitedSubsystem $ownedSubsystem, private readonly SubsystemIsolationHook $hook) {}

    public function subsystem(): ProhibitedSubsystem
    {
        return $this->ownedSubsystem;
    }

    public function proofReference(): string
    {
        return $this->hook->proofReference();
    }

    public function isIsolated(): bool
    {
        return $this->hook->isolated();
    }

    public function isolate(): void
    {
        if ($this->capturedSet) {
            throw new RuntimeIsolationException('FOUNDATION_ISOLATION_CONTROL_REENTRANT', 'An application isolation control is already active.');
        }
        $this->captured = $this->hook->capture();
        $this->capturedSet = true;
        $this->hook->isolate();
    }

    public function restore(bool $previouslyIsolated): void
    {
        if (! $this->capturedSet) {
            throw new RuntimeIsolationException('FOUNDATION_ISOLATION_CONTROL_NOT_ACTIVE', 'An application isolation control was not active.');
        }
        $this->hook->restore($this->captured);
        $this->captured = null;
        $this->capturedSet = false;
        if ($this->hook->isolated() !== $previouslyIsolated) {
            throw new RuntimeIsolationException('FOUNDATION_ISOLATION_CONTROL_RESTORE_UNPROVEN', 'An application isolation control did not restore its prior state.');
        }
    }
}
