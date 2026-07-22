<?php

namespace App\Services\LegacyMigration\Foundation\Storage;

use App\Models\LegacyMigration\Snapshot;
use App\Models\LegacyMigration\TargetCollisionSnapshot;
use App\Services\LegacyMigration\Foundation\Security\Phase3ProtectedStoreModelGuard;
use App\Services\LegacyMigration\Foundation\Security\ProtectedStoreAccessDeniedException;
use App\Services\LegacyMigration\Foundation\Security\ProtectedStoreOperationContext;
use Illuminate\Support\Facades\DB;

final class SnapshotRepository
{
    public function __construct(private readonly ?ProtectedRecordSecurityRepository $security = null) {}

    /** @param array<string, mixed> $attributes */
    public function append(array $attributes): Snapshot
    {
        Phase3ProtectedStoreModelGuard::assertLegacyUnsealedMethodAllowed();

        return $this->appendInternal($attributes);
    }

    /** @param array<string, mixed> $attributes */
    private function appendInternal(array $attributes): Snapshot
    {
        ProtectedToken::assert($attributes['snapshot_token'] ?? '', 'snapshot_token');
        ProtectedToken::assert($attributes['coordinate_token'] ?? '', 'coordinate_token');

        return Snapshot::query()->create($attributes);
    }

    /** @param array<string,mixed> $attributes @param array<string,array{encoded_token:string,domain:string}> $tokenSet */
    public function appendProtected(ProtectedStoreOperationContext $context, array $attributes, array $tokenSet): Snapshot
    {
        return DB::transaction(function () use ($context, $attributes, $tokenSet): Snapshot {
            $attributes = ProtectedTokenSet::apply($attributes, $tokenSet);
            $primary = ProtectedTokenSet::primary($tokenSet, 'snapshot_token');
            $record = $this->appendInternal($attributes);
            $isTarget = ($attributes['snapshot_kind'] ?? null) === 'target';
            $this->security()->seal($context, $record, $primary, ProtectedTokenSet::domain($tokenSet, 'snapshot_token'), (int) $attributes['run_id'], $isTarget ? null : (int) $record->id, $isTarget ? (int) $record->id : null, (string) $attributes['access_classification'], (string) $attributes['retention_classification'], $tokenSet);

            return $record;
        }, 3);
    }

    /** @param array<string, mixed> $attributes */
    public function appendTargetCollision(array $attributes): TargetCollisionSnapshot
    {
        Phase3ProtectedStoreModelGuard::assertLegacyUnsealedMethodAllowed();

        return $this->appendTargetCollisionInternal($attributes);
    }

    /** @param array<string, mixed> $attributes */
    private function appendTargetCollisionInternal(array $attributes): TargetCollisionSnapshot
    {
        ProtectedToken::assert($attributes['collision_snapshot_token'] ?? '', 'collision_snapshot_token');
        ProtectedToken::assert($attributes['collision_coordinate_token'] ?? '', 'collision_coordinate_token');

        return TargetCollisionSnapshot::query()->create($attributes);
    }

    /** @param array<string,mixed> $attributes @param array<string,array{encoded_token:string,domain:string}> $tokenSet */
    public function appendTargetCollisionProtected(ProtectedStoreOperationContext $context, array $attributes, array $tokenSet): TargetCollisionSnapshot
    {
        return DB::transaction(function () use ($context, $attributes, $tokenSet): TargetCollisionSnapshot {
            $attributes = ProtectedTokenSet::apply($attributes, $tokenSet);
            $primary = ProtectedTokenSet::primary($tokenSet, 'collision_snapshot_token');
            $record = $this->appendTargetCollisionInternal($attributes);
            $this->security()->seal($context, $record, $primary, ProtectedTokenSet::domain($tokenSet, 'collision_snapshot_token'), (int) $attributes['run_id'], null, (int) $attributes['target_snapshot_id'], (string) $attributes['access_classification'], (string) $attributes['retention_classification'], $tokenSet);

            return $record;
        }, 3);
    }

    public function readProtected(ProtectedStoreOperationContext $context, int $snapshotId): ProtectedRecordProjection
    {
        return $this->security()->readProjection($context, Snapshot::class, $snapshotId);
    }

    public function readTargetCollisionProtected(ProtectedStoreOperationContext $context, int $snapshotId): ProtectedRecordProjection
    {
        return $this->security()->readProjection($context, TargetCollisionSnapshot::class, $snapshotId);
    }

    private function security(): ProtectedRecordSecurityRepository
    {
        return $this->security ?? throw ProtectedStoreAccessDeniedException::forCode('LM-SEC-STORE-REPOSITORY-BOUNDARY-MISSING-001');
    }
}
