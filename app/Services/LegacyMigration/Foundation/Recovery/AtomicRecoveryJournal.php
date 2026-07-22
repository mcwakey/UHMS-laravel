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

    /** Append idempotently; a different checkpoint at the same stage must throw. */
    public function appendCheckpoint(AtomicIntentSnapshot $intent, RecoveryCheckpoint $checkpoint): void;

    /** Persist the classification using compare-and-set on the intent snapshot. */
    public function recordDecision(AtomicIntentSnapshot $intent, RecoveryDecision $decision): void;
}
