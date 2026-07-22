<?php

namespace App\Services\LegacyMigration\Foundation\Recovery;

use Closure;

/** Protected, foundation-only recovery adapter. It has no business-model dependency. */
final class LaravelAtomicRecoveryJournal implements AtomicRecoveryJournal
{
    public function __construct(private readonly ProtectedRecoveryStore $store) {}

    public function transaction(Closure $operation): mixed
    {
        return $this->store->transaction($operation);
    }

    public function resolveOrCreateIntent(AtomicIntentDescriptor $intent): AtomicIntentSnapshot
    {
        $coordinate = $this->store->coordinate($intent);

        return $this->store->resolveOrCreateIntent($intent, $coordinate);
    }

    public function observe(AtomicIntentSnapshot $intent, CrashBoundary $boundary): RecoveryObservation
    {
        return $this->store->observe($intent, $boundary, $this->store->coordinate($intent->descriptor));
    }

    public function replayDecision(AtomicIntentSnapshot $intent, CrashBoundary $boundary, RecoveryObservation $observation): ?RecoveryDecision
    {
        return $this->store->replayDecision($intent, $boundary, $observation, $this->store->coordinate($intent->descriptor));
    }

    public function appendCheckpoint(AtomicIntentSnapshot $intent, RecoveryCheckpoint $checkpoint): void
    {
        $this->store->appendCheckpoint($intent, $checkpoint, $this->store->coordinate($intent->descriptor));
    }

    public function recordDecision(AtomicIntentSnapshot $intent, RecoveryDecision $decision, RecoveryObservation $observation): void
    {
        $coordinate = $this->store->coordinate($intent->descriptor);
        if ($this->store->existingDecision($intent, $decision, $observation->durableEvidenceHash, $coordinate)) {
            return;
        }
        $this->store->recordDecision(
            $intent,
            $decision,
            $observation->evidence,
            $observation->durableEvidenceHash,
            $coordinate,
            $this->store->currentAttemptCount($intent),
        );
    }
}
