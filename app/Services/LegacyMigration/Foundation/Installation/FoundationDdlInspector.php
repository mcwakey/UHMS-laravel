<?php

namespace App\Services\LegacyMigration\Foundation\Installation;

use RuntimeException;

final class FoundationDdlInspector
{
    /**
     * @param  list<DdlObjectExpectation>  $expected
     * @param  list<DdlObjectObservation>  $observed
     * @return list<DdlInspectionResult>
     */
    public function inspect(array $expected, array $observed): array
    {
        $expectedByCoordinate = [];
        foreach ($expected as $item) {
            if (isset($expectedByCoordinate[$item->coordinate()])) {
                throw new RuntimeException('Duplicate expected foundation DDL coordinate.');
            }
            $expectedByCoordinate[$item->coordinate()] = $item;
        }
        $observedByCoordinate = [];
        foreach ($observed as $item) {
            if (isset($observedByCoordinate[$item->coordinate()])) {
                throw new RuntimeException('Ambiguous observed foundation DDL coordinate.');
            }
            $observedByCoordinate[$item->coordinate()] = $item;
            if (! isset($expectedByCoordinate[$item->coordinate()])
                && (str_starts_with($item->name, 'legacy_migration_') || str_starts_with($item->name, 'lm_'))) {
                throw new DdlInspectionException(
                    DdlInspectionState::Conflicting,
                    'Unapproved object exists in the reserved foundation DDL namespace.',
                );
            }
        }

        $results = [];
        foreach ($expected as $item) {
            $actual = $observedByCoordinate[$item->coordinate()] ?? null;
            if ($actual === null) {
                $state = $item->metadataOnly
                    ? DdlInspectionState::RepairableMetadataGap
                    : DdlInspectionState::Absent;
            } else {
                $state = hash_equals($item->definitionHash, $actual->definitionHash)
                    ? DdlInspectionState::Matching
                    : DdlInspectionState::Drifted;
            }
            $results[] = new DdlInspectionResult($item, $state, $actual?->definitionHash);
        }

        return $results;
    }
}
