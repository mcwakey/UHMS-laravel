<?php

namespace App\Services\LegacyMigration\Foundation\Reconciliation;

use InvalidArgumentException;

final class ReconciliationVerdictService
{
    /**
     * @param  list<string>  $mandatoryMeasurementIds
     * @param  list<array{id:string,difference:int|float|string|null,classification?:string}>  $measurements
     * @return array{verdict:ReconciliationVerdict,missing:list<string>,nonzero:list<string>,unexplained:list<string>}
     */
    public function assess(array $mandatoryMeasurementIds, array $measurements): array
    {
        $byId = [];
        foreach ($measurements as $measurement) {
            $id = $measurement['id'] ?? null;
            if (! is_string($id) || $id === '' || isset($byId[$id])) {
                throw new InvalidArgumentException('Reconciliation measurements require unique non-empty identifiers.');
            }
            $byId[$id] = $measurement;
        }

        $missing = array_values(array_filter(
            $mandatoryMeasurementIds,
            fn (string $id): bool => ! array_key_exists($id, $byId) || $byId[$id]['difference'] === null,
        ));

        if ($missing !== []) {
            return $this->result(ReconciliationVerdict::BlockedNotMeasured, $missing);
        }

        $nonzero = [];
        $unexplained = [];
        foreach ($mandatoryMeasurementIds as $id) {
            $measurement = $byId[$id];
            if (! $this->isExactZero($measurement['difference'])) {
                $nonzero[] = $id;
            }
            if (($measurement['classification'] ?? 'explained') === 'unexplained') {
                $unexplained[] = $id;
            }
        }

        if ($unexplained !== []) {
            return $this->result(ReconciliationVerdict::FailedUnexplained, [], $nonzero, $unexplained);
        }

        if ($nonzero !== []) {
            return $this->result(ReconciliationVerdict::FailedNonZeroDifference, [], $nonzero);
        }

        return $this->result(ReconciliationVerdict::Passed);
    }

    private function isExactZero(int|float|string $value): bool
    {
        if (is_int($value)) {
            return $value === 0;
        }
        if (is_float($value)) {
            return $value === 0.0;
        }

        return preg_match('/^[+-]?0+(?:\.0+)?$/D', trim($value)) === 1;
    }

    /** @return array{verdict:ReconciliationVerdict,missing:list<string>,nonzero:list<string>,unexplained:list<string>} */
    private function result(
        ReconciliationVerdict $verdict,
        array $missing = [],
        array $nonzero = [],
        array $unexplained = [],
    ): array {
        return compact('verdict', 'missing', 'nonzero', 'unexplained');
    }
}
