<?php

namespace App\Services\LegacyMigration\Foundation\Storage;

use App\Models\LegacyMigration\AtomicIntent;
use App\Models\LegacyMigration\ProtectedFoundationModel;
use App\Models\LegacyMigration\RecoveryJournalEntry;
use App\Services\LegacyMigration\Foundation\Recovery\ProtectedRecoveryWrite;
use App\Services\LegacyMigration\Foundation\Recovery\RecoveryException;
use App\Services\LegacyMigration\Foundation\Security\ProtectedStoreAccessDeniedException;
use App\Services\LegacyMigration\Foundation\Security\ProtectedStoreOperationContext;
use Illuminate\Support\Facades\DB;

/** Sealed persistence boundary for the Phase 3B recovery journal and intent CAS. */
final class RecoveryJournalRepository
{
    public function __construct(private readonly ?ProtectedRecordSecurityRepository $security = null) {}

    /** @param array<string,mixed> $fixed */
    public function appendProtected(ProtectedRecoveryWrite $write, array $fixed): RecoveryJournalEntry
    {
        return DB::transaction(function () use ($write, $fixed): RecoveryJournalEntry {
            $attributes = ProtectedTokenSet::apply(array_replace($write->attributes, $fixed), $write->tokenSet);
            $record = RecoveryJournalEntry::query()->create($attributes);
            $this->security()->seal(
                $write->context,
                $record,
                ProtectedTokenSet::primary($write->tokenSet, 'journal_token'),
                ProtectedTokenSet::domain($write->tokenSet, 'journal_token'),
                (int) $attributes['run_id'],
                (int) $attributes['source_snapshot_id'],
                isset($attributes['target_snapshot_id']) ? (int) $attributes['target_snapshot_id'] : null,
                (string) $attributes['access_classification'],
                (string) $attributes['retention_classification'],
                $write->tokenSet,
            );

            return $record;
        }, 3);
    }

    public function transitionIntentProtected(
        ProtectedStoreOperationContext $context,
        int $intentId,
        string $expectedState,
        int $expectedVersion,
        int $expectedAttempt,
        string $nextState,
        int $nextAttempt,
    ): ProtectedRecordProjection {
        return $this->security()->transition(
            $context,
            AtomicIntent::class,
            $intentId,
            function (ProtectedFoundationModel $record) use ($expectedState, $expectedVersion, $expectedAttempt, $nextState, $nextAttempt): ProtectedFoundationModel {
                $updated = $record->newQuery()
                    ->whereKey($record->getKey())
                    ->where('state', $expectedState)
                    ->where('lock_version', $expectedVersion)
                    ->where('transition_attempt_count', $expectedAttempt)
                    ->update([
                        'state' => $nextState,
                        'lock_version' => $expectedVersion + 1,
                        'transition_attempt_count' => $nextAttempt,
                        'updated_at' => now(),
                    ]);
                if ($updated !== 1) {
                    throw RecoveryException::failClosed('RECOVERY-STATE-CAS-CONFLICT');
                }

                return $record->newQuery()->findOrFail($record->getKey());
            },
        );
    }

    public function touchIntentProtected(
        ProtectedStoreOperationContext $context,
        int $intentId,
        string $expectedState,
        int $expectedVersion,
        int $expectedAttempt,
    ): ProtectedRecordProjection {
        return $this->security()->transition(
            $context,
            AtomicIntent::class,
            $intentId,
            function (ProtectedFoundationModel $record) use ($expectedState, $expectedVersion, $expectedAttempt): ProtectedFoundationModel {
                $updated = $record->newQuery()
                    ->whereKey($record->getKey())
                    ->where('state', $expectedState)
                    ->where('lock_version', $expectedVersion)
                    ->where('transition_attempt_count', $expectedAttempt)
                    ->update(['lock_version' => $expectedVersion + 1, 'updated_at' => now()]);
                if ($updated !== 1) {
                    throw RecoveryException::failClosed('RECOVERY-DECISION-CAS-CONFLICT');
                }

                return $record->newQuery()->findOrFail($record->getKey());
            },
        );
    }

    public function readProtected(ProtectedStoreOperationContext $context, int $recordId, array $expected = []): ProtectedRecordProjection
    {
        return $this->security()->readProjection($context, RecoveryJournalEntry::class, $recordId, $expected);
    }

    private function security(): ProtectedRecordSecurityRepository
    {
        return $this->security ?? throw ProtectedStoreAccessDeniedException::forCode('LM-SEC-STORE-REPOSITORY-BOUNDARY-MISSING-001');
    }
}
