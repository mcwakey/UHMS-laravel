<?php

namespace App\Services\LegacyMigration\Foundation\Storage;

use App\Models\LegacyMigration\AtomicIntent;
use App\Models\LegacyMigration\Checkpoint;
use App\Models\LegacyMigration\CompensationRecord;
use App\Models\LegacyMigration\ProtectedFoundationModel;
use App\Services\LegacyMigration\Foundation\Recovery\AtomicUnit;
use App\Services\LegacyMigration\Foundation\Recovery\CompensationPlanRegistry;
use App\Services\LegacyMigration\Foundation\Recovery\MigrationState;
use App\Services\LegacyMigration\Foundation\Recovery\MonotonicStateMachine;
use App\Services\LegacyMigration\Foundation\Security\Phase3ProtectedStoreModelGuard;
use App\Services\LegacyMigration\Foundation\Security\ProtectedStoreAccessDeniedException;
use App\Services\LegacyMigration\Foundation\Security\ProtectedStoreOperationContext;
use Illuminate\Support\Facades\DB;

final class RecoveryRepository
{
    public function __construct(
        private readonly CompareAndSet $compareAndSet = new CompareAndSet,
        private readonly ?ProtectedRecordSecurityRepository $security = null,
    ) {}

    /** @param array<string, mixed> $attributes */
    public function createIntent(array $attributes): AtomicIntent
    {
        Phase3ProtectedStoreModelGuard::assertLegacyUnsealedMethodAllowed();

        return $this->createIntentInternal($attributes);
    }

    /** @param array<string, mixed> $attributes */
    private function createIntentInternal(array $attributes): AtomicIntent
    {
        ProtectedToken::assert($attributes['intent_token'] ?? '', 'intent_token');

        return AtomicIntent::query()->create($attributes);
    }

    /** @param array<string,mixed> $attributes @param array<string,array{encoded_token:string,domain:string}> $tokenSet */
    public function createIntentProtected(ProtectedStoreOperationContext $context, array $attributes, array $tokenSet): AtomicIntent
    {
        return DB::transaction(function () use ($context, $attributes, $tokenSet): AtomicIntent {
            $attributes = ProtectedTokenSet::apply($attributes, $tokenSet);
            $primary = ProtectedTokenSet::primary($tokenSet, 'intent_token');
            $record = $this->createIntentInternal($attributes);
            $this->security()->seal(
                $context,
                $record,
                $primary,
                ProtectedTokenSet::domain($tokenSet, 'intent_token'),
                (int) $attributes['run_id'],
                null,
                null,
                (string) $attributes['access_classification'],
                (string) $attributes['retention_classification'],
                $tokenSet,
            );

            return $record;
        }, 3);
    }

    /** @param array<string, mixed> $evidence */
    public function advanceIntent(AtomicIntent $intent, string $expectedState, int $expectedVersion, string $nextState, array $evidence = []): AtomicIntent
    {
        Phase3ProtectedStoreModelGuard::assertLegacyMutationAllowed($intent);
        $expected = MigrationState::tryFrom($expectedState);
        $next = MigrationState::tryFrom($nextState);
        if ($expected === null || $next === null) {
            throw new StorageIntegrityException('The recovery state transition is invalid.');
        }
        MonotonicStateMachine::assertTransitionAllowed($expected, $next);
        /** @var AtomicIntent $advanced */
        $advanced = $this->compareAndSet->state($intent, $expectedState, $expectedVersion, $nextState, $evidence);

        return $advanced;
    }

    /** @param array<string,mixed> $evidence */
    public function advanceIntentProtected(
        ProtectedStoreOperationContext $context,
        int $intentId,
        string $expectedState,
        int $expectedVersion,
        string $nextState,
        array $evidence = [],
    ): ProtectedRecordProjection {
        return $this->security()->transition(
            $context,
            AtomicIntent::class,
            $intentId,
            fn ($record) => $this->advanceIntent($record, $expectedState, $expectedVersion, $nextState, $evidence),
        );
    }

    /** @param array<string, mixed> $attributes */
    public function appendCheckpoint(array $attributes): Checkpoint
    {
        Phase3ProtectedStoreModelGuard::assertLegacyUnsealedMethodAllowed();

        return $this->appendCheckpointInternal($attributes);
    }

    /** @param array<string, mixed> $attributes */
    private function appendCheckpointInternal(array $attributes): Checkpoint
    {
        if (empty($attributes['transaction_evidence_hash']) || empty($attributes['reconciliation_bundle_hash'])) {
            throw new StorageIntegrityException('A checkpoint requires durable transaction and reconciliation evidence.');
        }

        return Checkpoint::query()->create($attributes);
    }

    /** @param array<string,mixed> $attributes @param array<string,array{encoded_token:string,domain:string}> $tokenSet */
    public function appendCheckpointProtected(ProtectedStoreOperationContext $context, array $attributes, array $tokenSet): Checkpoint
    {
        return DB::transaction(function () use ($context, $attributes, $tokenSet): Checkpoint {
            $attributes = ProtectedTokenSet::apply($attributes, $tokenSet);
            $primary = ProtectedTokenSet::primary($tokenSet, 'dependency_chain_token');
            $record = $this->appendCheckpointInternal($attributes);
            $this->security()->seal(
                $context,
                $record,
                $primary,
                ProtectedTokenSet::domain($tokenSet, 'dependency_chain_token'),
                (int) $attributes['run_id'],
                null,
                null,
                (string) $attributes['access_classification'],
                (string) $attributes['retention_classification'],
                $tokenSet,
            );

            return $record;
        }, 3);
    }

    /** @param array<string, mixed> $attributes */
    public function appendCompensation(array $attributes): CompensationRecord
    {
        Phase3ProtectedStoreModelGuard::assertLegacyUnsealedMethodAllowed();

        return $this->appendCompensationInternal($attributes);
    }

    /** @param array<string, mixed> $attributes */
    private function appendCompensationInternal(array $attributes): CompensationRecord
    {
        ProtectedToken::assert($attributes['compensation_token'] ?? '', 'compensation_token');

        try {
            $unit = AtomicUnit::from((string) ($attributes['unit_name'] ?? ''));
            (new CompensationPlanRegistry)->forUnit($unit)->assertActionAllowed((string) ($attributes['action_type'] ?? ''));
        } catch (\Throwable) {
            throw new StorageIntegrityException('The compensation action is not authorized for its atomic unit.');
        }

        return CompensationRecord::query()->create($attributes);
    }

    /** @param array<string,mixed> $attributes @param array<string,array{encoded_token:string,domain:string}> $tokenSet */
    public function appendCompensationProtected(ProtectedStoreOperationContext $context, array $attributes, array $tokenSet): CompensationRecord
    {
        return DB::transaction(function () use ($context, $attributes, $tokenSet): CompensationRecord {
            $attributes = ProtectedTokenSet::apply($attributes, $tokenSet);
            $primary = ProtectedTokenSet::primary($tokenSet, 'compensation_token');
            $record = $this->appendCompensationInternal($attributes);
            $this->security()->seal(
                $context,
                $record,
                $primary,
                ProtectedTokenSet::domain($tokenSet, 'compensation_token'),
                (int) $attributes['run_id'],
                null,
                null,
                (string) $attributes['access_classification'],
                (string) $attributes['retention_classification'],
                $tokenSet,
            );

            return $record;
        }, 3);
    }

    /** @param class-string<ProtectedFoundationModel> $recordType */
    public function readProtected(ProtectedStoreOperationContext $context, string $recordType, int $recordId): ProtectedRecordProjection
    {
        if (! in_array($recordType, [AtomicIntent::class, Checkpoint::class, CompensationRecord::class], true)) {
            throw new StorageIntegrityException('Unsupported protected recovery record type.');
        }

        return $this->security()->readProjection($context, $recordType, $recordId);
    }

    private function security(): ProtectedRecordSecurityRepository
    {
        return $this->security ?? throw ProtectedStoreAccessDeniedException::forCode('LM-SEC-STORE-REPOSITORY-BOUNDARY-MISSING-001');
    }
}
