<?php

namespace App\Services\LegacyMigration\Foundation\Recovery;

use Closure;

interface AtomicRecoveryJournal
{
    /**
     * @template T
     *
     * @param  Closure(): T  $operation
     * @return T
     */
    public function transaction(Closure $operation): mixed;

    /** Resolve the exact compatible intent or create it once; conflict must throw. */
    public function resolveOrCreateIntent(AtomicIntentDescriptor $intent): AtomicIntentSnapshot;

    /** Derive recovery facts from the protected durable stores for this exact coordinate. */
    public function observe(AtomicIntentSnapshot $intent, CrashBoundary $boundary): RecoveryObservation;

    /** Return the already sealed compatible decision for an idempotent replay. */
    public function replayDecision(AtomicIntentSnapshot $intent, CrashBoundary $boundary, RecoveryObservation $observation): ?RecoveryDecision;

    /** Append idempotently; a different checkpoint at the same stage must throw. */
    public function appendCheckpoint(AtomicIntentSnapshot $intent, RecoveryCheckpoint $checkpoint): void;

    /** Persist the classification using compare-and-set on the intent snapshot. */
    public function recordDecision(AtomicIntentSnapshot $intent, RecoveryDecision $decision, RecoveryObservation $observation): void;
}
