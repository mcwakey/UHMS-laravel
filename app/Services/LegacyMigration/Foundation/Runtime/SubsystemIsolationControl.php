<?php

namespace App\Services\LegacyMigration\Foundation\Runtime;

/**
 * Application wiring supplies one control for every prohibited subsystem.
 * A control must operate on the real application hook, not a configuration
 * assumption, and report the observed state after each transition.
 */
interface SubsystemIsolationControl
{
    public function subsystem(): ProhibitedSubsystem;

    public function isIsolated(): bool;

    public function isolate(): void;

    public function restore(bool $previouslyIsolated): void;
}
