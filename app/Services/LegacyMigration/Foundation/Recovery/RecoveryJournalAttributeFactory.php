<?php

namespace App\Services\LegacyMigration\Foundation\Recovery;

use App\Services\LegacyMigration\Foundation\Security\ProtectedStoreOperationContext;

/** Builds recovery records only from verified durable coordinates and pinned authority. */
interface RecoveryJournalAttributeFactory
{
    public function intentToken(
        string $idempotencyToken,
        AtomicUnit $unit,
        MigrationState $expectedPriorState,
        int $attempt,
        string $inputFingerprint,
    ): string;

    public function readContext(string $recordKind, RecoveryCoordinateHint $hint): ProtectedStoreOperationContext;

    public function verifyCoordinate(VerifiedRecoveryCoordinate $coordinate): void;

    public function intent(AtomicIntentDescriptor $descriptor, VerifiedRecoveryCoordinate $coordinate): ProtectedRecoveryWrite;

    public function checkpoint(
        AtomicIntentSnapshot $intent,
        RecoveryCheckpoint $checkpoint,
        VerifiedRecoveryCoordinate $coordinate,
    ): ProtectedRecoveryWrite;

    public function decision(
        AtomicIntentSnapshot $intent,
        RecoveryDecision $decision,
        RecoveryEvidence $evidence,
        VerifiedRecoveryCoordinate $coordinate,
        int $resultingLockVersion,
        int $attemptCount,
    ): ProtectedRecoveryWrite;

    public function transition(
        AtomicIntentSnapshot $intent,
        MigrationState $next,
        VerifiedRecoveryCoordinate $coordinate,
        int $resultingVersion,
        int $resultingAttempt,
    ): ProtectedRecoveryWrite;
}
