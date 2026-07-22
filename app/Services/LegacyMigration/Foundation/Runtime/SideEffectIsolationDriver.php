<?php

namespace App\Services\LegacyMigration\Foundation\Runtime;

interface SideEffectIsolationDriver
{
    /** @param array<int, ProhibitedSubsystem> $subsystems */
    public function capture(array $subsystems): SideEffectIsolationSnapshot;

    /** @param array<int, ProhibitedSubsystem> $subsystems */
    public function isolate(array $subsystems): void;

    public function isIsolated(ProhibitedSubsystem $subsystem): bool;

    public function restore(SideEffectIsolationSnapshot $snapshot): void;

    public function matches(SideEffectIsolationSnapshot $snapshot): bool;
}
