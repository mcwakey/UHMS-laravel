<?php

namespace App\Services\LegacyMigration\Foundation\Runtime;

/** Permanent call boundary for pre-resolved and directly-created services. */
final class OperationalEffectGate
{
    public static function assertAllowed(ProhibitedSubsystem $subsystem): void
    {
        if (! function_exists('app') || ! app()->bound(ApplicationIsolationState::class)) {
            return;
        }

        app(ApplicationIsolationState::class)->assertInvocationAllowed($subsystem);
    }
}
