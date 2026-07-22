<?php

namespace App\Services\LegacyMigration\Foundation\Reconciliation;

use App\Services\LegacyMigration\Foundation\Snapshot\SnapshotManifest;

/** Trusted run-internal adapter; production has no default binding. */
interface AuthoritativeAggregateObservationProvider
{
    /** @param array<string,int> $repositoryBefore @param array<string,int> $repositoryAfter */
    public function capture(
        SnapshotManifest $source,
        SnapshotManifest $targetBefore,
        SnapshotManifest $targetAfter,
        array $repositoryBefore,
        array $repositoryAfter,
    ): AuthoritativeAggregateObservation;
}
