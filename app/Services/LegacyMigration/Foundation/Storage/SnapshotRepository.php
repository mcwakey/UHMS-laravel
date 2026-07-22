<?php

namespace App\Services\LegacyMigration\Foundation\Storage;

use App\Models\LegacyMigration\Snapshot;
use App\Models\LegacyMigration\TargetCollisionSnapshot;

final class SnapshotRepository
{
    /** @param array<string, mixed> $attributes */
    public function append(array $attributes): Snapshot
    {
        ProtectedToken::assert($attributes['snapshot_token'] ?? '', 'snapshot_token');
        ProtectedToken::assert($attributes['coordinate_token'] ?? '', 'coordinate_token');

        return Snapshot::query()->create($attributes);
    }

    /** @param array<string, mixed> $attributes */
    public function appendTargetCollision(array $attributes): TargetCollisionSnapshot
    {
        ProtectedToken::assert($attributes['collision_snapshot_token'] ?? '', 'collision_snapshot_token');
        ProtectedToken::assert($attributes['collision_coordinate_token'] ?? '', 'collision_coordinate_token');

        return TargetCollisionSnapshot::query()->create($attributes);
    }
}
