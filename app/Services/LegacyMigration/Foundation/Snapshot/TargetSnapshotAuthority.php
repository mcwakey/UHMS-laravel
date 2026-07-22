<?php

namespace App\Services\LegacyMigration\Foundation\Snapshot;

use App\Services\LegacyMigration\Foundation\Environment\MetadataConnection;
use App\Services\LegacyMigration\Foundation\Environment\SchemaObservation;

interface TargetSnapshotAuthority
{
    /** Returns a redacted approved physical-identity record digest after direct connection verification. */
    public function verify(MetadataConnection $connection, SchemaObservation $structural, string $configurationFingerprint): string;
}
