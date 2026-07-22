<?php

namespace App\Services\LegacyMigration\Foundation\Installation;

use RuntimeException;

final class DdlRecoveryPlanner
{
    /** @param list<DdlInspectionResult> $results */
    public function plan(string $installationVersion, array $results): DdlRecoveryPlan
    {
        if ($installationVersion === '' || $results === []) {
            throw new RuntimeException('A version-bound foundation DDL manifest is required.');
        }
        $operations = [];
        $matching = 0;
        foreach ($results as $result) {
            if (! hash_equals($installationVersion, $result->expected->migrationVersion)) {
                throw new RuntimeException('Foundation DDL manifest version mismatch.');
            }
            if (in_array($result->state, [DdlInspectionState::Drifted, DdlInspectionState::Conflicting], true)) {
                throw new RuntimeException('Foundation DDL drift is not automatically repairable.');
            }
            if ($result->state === DdlInspectionState::Matching) {
                $matching++;

                continue;
            }
            $operations[] = $result->expected;
        }

        return new DdlRecoveryPlan(
            $installationVersion,
            $operations,
            $matching > 0 && $operations !== [],
            $matching === count($results),
        );
    }
}
