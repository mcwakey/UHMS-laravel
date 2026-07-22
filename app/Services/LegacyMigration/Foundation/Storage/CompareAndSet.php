<?php

namespace App\Services\LegacyMigration\Foundation\Storage;

use App\Models\LegacyMigration\ProtectedFoundationModel;
use Illuminate\Support\Facades\DB;
use App\Services\LegacyMigration\Foundation\Security\Phase3ProtectedStoreModelGuard;

final class CompareAndSet
{
    /** @param array<string, mixed> $changes */
    public function state(ProtectedFoundationModel $model, string $expectedState, int $expectedVersion, string $nextState, array $changes = []): ProtectedFoundationModel
    {
        Phase3ProtectedStoreModelGuard::assertConnectionWriteAllowed($model->getConnectionName());
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
}
