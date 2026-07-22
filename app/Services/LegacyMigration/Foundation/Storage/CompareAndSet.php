<?php

namespace App\Services\LegacyMigration\Foundation\Storage;

use App\Models\LegacyMigration\ProtectedFoundationModel;
use App\Services\LegacyMigration\Foundation\Security\Phase3ProtectedStoreModelGuard;
use App\Services\LegacyMigration\Foundation\Security\ProtectedStoreAccessDeniedException;
use App\Services\LegacyMigration\Foundation\Security\ProtectedStoreOperationContext;
use Illuminate\Support\Facades\DB;

final class CompareAndSet
{
    public function __construct(private readonly ?ProtectedRecordSecurityRepository $security = null) {}

    /** @param array<string, mixed> $changes */
    public function state(ProtectedFoundationModel $model, string $expectedState, int $expectedVersion, string $nextState, array $changes = []): ProtectedFoundationModel
    {
        Phase3ProtectedStoreModelGuard::assertLegacyMutationAllowed($model);
        MigrationStateTransitions::assertAllowed($expectedState, $nextState);
        $reserved = ['id', 'state', 'lock_version', 'created_at', 'updated_at'];
        if (array_intersect($reserved, array_keys($changes)) !== []) {
            throw new StorageIntegrityException('Compare-and-set metadata cannot override protected state coordinates.');
        }

        return DB::transaction(function () use ($model, $expectedState, $expectedVersion, $nextState, $changes): ProtectedFoundationModel {
            $updated = $model->newQuery()
                ->whereKey($model->getKey())
                ->where('state', $expectedState)
                ->where('lock_version', $expectedVersion)
                ->update(array_merge($changes, [
                    'state' => $nextState,
                    'lock_version' => $expectedVersion + 1,
                    'updated_at' => now(),
                ]));

            if ($updated !== 1) {
                throw new StorageIntegrityException('Migration state compare-and-set failed; the durable record changed or lineage is incompatible.');
            }

            return $model->newQuery()->findOrFail($model->getKey());
        }, 3);
    }

    /**
     * Performs the same CAS under a verified envelope and appends a new keyed
     * envelope generation. No mutable model escapes the repository boundary.
     *
     * @param  class-string<ProtectedFoundationModel>  $recordType
     * @param  array<string, mixed>  $changes
     */
    public function stateProtected(
        ProtectedStoreOperationContext $context,
        string $recordType,
        int $recordId,
        string $expectedState,
        int $expectedVersion,
        string $nextState,
        array $changes = [],
    ): ProtectedRecordProjection {
        return $this->security()->transition(
            $context,
            $recordType,
            $recordId,
            fn (ProtectedFoundationModel $record): ProtectedFoundationModel => $this->state(
                $record,
                $expectedState,
                $expectedVersion,
                $nextState,
                $changes,
            ),
        );
    }

    private function security(): ProtectedRecordSecurityRepository
    {
        return $this->security ?? throw ProtectedStoreAccessDeniedException::forCode('LM-SEC-STORE-REPOSITORY-BOUNDARY-MISSING-001');
    }
}
