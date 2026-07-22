<?php

namespace App\Services\LegacyMigration\Foundation\Reconciliation;

use App\Services\LegacyMigration\Foundation\Snapshot\SnapshotManifest;

final class ObservedEmptyCohortAggregateProvider implements AuthoritativeAggregateObservationProvider
{
    public function capture(
        SnapshotManifest $source,
        SnapshotManifest $targetBefore,
        SnapshotManifest $targetAfter,
        array $repositoryBefore,
        array $repositoryAfter,
    ): AuthoritativeAggregateObservation {
        unset($repositoryBefore, $repositoryAfter);

        return AuthoritativeAggregateObservation::fromObservedEmptyCohort($source, $targetBefore, $targetAfter);
    }
}
