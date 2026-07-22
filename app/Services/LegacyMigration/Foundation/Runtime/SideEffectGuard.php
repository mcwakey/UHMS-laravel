<?php

namespace App\Services\LegacyMigration\Foundation\Runtime;

final class SideEffectGuard
{
    public function __construct(
        private readonly MigrationRuntimeContext $context,
        private readonly SideEffectIsolationRegistry $registry,
        private readonly SideEffectCounter $counter,
    ) {}

    /**
     * Operational behavior is unchanged outside migration mode. Inside an
     * active migration scope, every registered effect is denied and counted.
     */
    public function assertAllowed(ProhibitedSubsystem $subsystem): void
    {
        if (! $this->context->active()) {
            return;
        }
        if (! $this->registry->denies($subsystem)) {
            throw new RuntimeIsolationException('FOUNDATION_ISOLATION_GUARD_UNREGISTERED', 'A side-effect guard is not registered.');
        }

        $this->counter->record($subsystem);

        throw new RuntimeIsolationException('FOUNDATION_SIDE_EFFECT_DENIED', 'A prohibited side effect was denied during migration execution.');
    }

    public function assertOutboundAllowed(OutboundChannel $channel): void
    {
        if (! $this->context->active()) {
            return;
        }
        if (! $this->registry->deniesOutbound($channel)) {
            throw new RuntimeIsolationException('FOUNDATION_OUTBOUND_GUARD_UNREGISTERED', 'An outbound integration guard is not registered.');
        }

        $this->counter->record($channel->subsystem());

        throw new RuntimeIsolationException('FOUNDATION_OUTBOUND_DENIED', 'A prohibited outbound integration was denied during migration execution.');
    }
}
