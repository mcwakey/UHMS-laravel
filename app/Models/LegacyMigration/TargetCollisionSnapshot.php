<?php

namespace App\Models\LegacyMigration;

use App\Models\LegacyMigration\Concerns\AppendOnly;

class TargetCollisionSnapshot extends ProtectedFoundationModel
{
    use AppendOnly;

    protected $table = 'legacy_migration_target_collision_snapshots';

    protected function casts(): array
    {
        return parent::casts() + ['encrypted_evidence' => 'encrypted:array'];
    }
}
