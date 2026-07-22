<?php

namespace App\Services\LegacyMigration\Foundation\Storage;

use App\Models\LegacyMigration\IdempotencyRecord;
use App\Services\LegacyMigration\Foundation\Security\Phase3ProtectedStoreModelGuard;
use App\Services\LegacyMigration\Foundation\Security\ProtectedStoreAccessDeniedException;
use App\Services\LegacyMigration\Foundation\Security\ProtectedStoreOperationContext;
use Illuminate\Support\Facades\DB;

final class IdempotencyRepository
{
    public function __construct(
        private readonly CompareAndSet $compareAndSet = new CompareAndSet,
        private readonly ?ProtectedRecordSecurityRepository $security = null,
    ) {}

    /** @param array<string, mixed> $attributes */
    public function resolveOrCreate(array $attributes): IdempotencyRecord
    {
        Phase3ProtectedStoreModelGuard::assertLegacyUnsealedMethodAllowed();

        return $this->resolveOrCreateInternal($attributes);
    }

    /** @param array<string, mixed> $attributes */
    private function resolveOrCreateInternal(array $attributes): IdempotencyRecord
    {
        ProtectedToken::assert($attributes['idempotency_token'] ?? '', 'idempotency_token');

        return DB::transaction(function () use ($attributes): IdempotencyRecord {
            $existing = IdempotencyRecord::query()
                ->where('domain', $attributes['domain'])
                ->where('idempotency_token', $attributes['idempotency_token'])
                ->lockForUpdate()
                ->first();

            if ($existing !== null) {
                foreach ([
                    'run_id', 'source_snapshot_id', 'target_snapshot_id',
                    'protected_source_token', 'protected_target_token',
                    'input_fingerprint', 'outcome_type', 'contract_version',
                    'transformation_version', 'canonicalization_version',
                    'hmac_key_version',
                ] as $field) {
                    if ((string) $existing->{$field} !== (string) ($attributes[$field] ?? null)) {
                        throw new StorageIntegrityException('Idempotency key resolved to incompatible lineage.');
                    }
                }

                return $existing;
            }

            return IdempotencyRecord::query()->create($attributes);
        }, 3);
    }

    /**
     * Resolves or creates an idempotency record through the protected envelope
     * boundary. Existing rows are accepted only after keyed integrity and all
     * immutable lineage coordinates have been verified.
     *
     * @param  array<string, mixed>  $attributes
     * @param  array<string, array{encoded_token:string, domain:string}>  $tokenSet
     */
    public function resolveOrCreateProtected(
        ProtectedStoreOperationContext $context,
        array $attributes,
        array $tokenSet,
    ): ProtectedRecordProjection {
        return DB::transaction(function () use ($context, $attributes, $tokenSet): ProtectedRecordProjection {
            $attributes = ProtectedTokenSet::apply($attributes, $tokenSet);
            $primary = ProtectedTokenSet::primary($tokenSet, 'idempotency_token');
            $existingId = DB::table('legacy_migration_idempotency_records')
                ->where('domain', $attributes['domain'])
                ->where('idempotency_token', $attributes['idempotency_token'])
                ->lockForUpdate()
                ->value('id');
            $expected = [];
            foreach ([
                'run_id', 'source_snapshot_id', 'target_snapshot_id',
                'protected_source_token', 'protected_target_token',
                'input_fingerprint', 'outcome_type', 'contract_version',
                'transformation_version', 'canonicalization_version',
                'hmac_key_version',
            ] as $field) {
                $expected[$field] = $attributes[$field] ?? null;
            }

            if ($existingId !== null) {
                return $this->security()->readProjection($context, IdempotencyRecord::class, (int) $existingId, $expected);
            }

            $record = $this->resolveOrCreateInternal($attributes);
            $this->security()->seal(
                $context,
                $record,
                $primary,
                ProtectedTokenSet::domain($tokenSet, 'idempotency_token'),
                (int) $attributes['run_id'],
                (int) $attributes['source_snapshot_id'],
                isset($attributes['target_snapshot_id']) ? (int) $attributes['target_snapshot_id'] : null,
                (string) $attributes['access_classification'],
                (string) $attributes['retention_classification'],
                $tokenSet,
            );

            return $this->security()->readProjection($context, IdempotencyRecord::class, (int) $record->id, $expected);
        }, 3);
    }

    /** @param array<string, mixed> $outcome */
    public function advance(IdempotencyRecord $record, string $expectedState, int $expectedVersion, string $nextState, array $outcome = []): IdempotencyRecord
    {
        Phase3ProtectedStoreModelGuard::assertLegacyMutationAllowed($record);
        /** @var IdempotencyRecord $advanced */
        $advanced = $this->compareAndSet->state($record, $expectedState, $expectedVersion, $nextState, $outcome + [
            'attempt_count' => DB::raw('attempt_count + 1'),
        ]);

        return $advanced;
    }

    /** @param array<string, mixed> $outcome */
    public function advanceProtected(
        ProtectedStoreOperationContext $context,
        int $recordId,
        string $expectedState,
        int $expectedVersion,
        string $nextState,
        array $outcome = [],
    ): ProtectedRecordProjection {
        return $this->security()->transition(
            $context,
            IdempotencyRecord::class,
            $recordId,
            fn ($record) => $this->advance($record, $expectedState, $expectedVersion, $nextState, $outcome),
        );
    }

    private function security(): ProtectedRecordSecurityRepository
    {
        return $this->security ?? throw ProtectedStoreAccessDeniedException::forCode('LM-SEC-STORE-REPOSITORY-BOUNDARY-MISSING-001');
    }
}
