<?php

namespace App\Services\LegacyMigration\Foundation\Reconciliation;

final readonly class AuthoritativeMeasurement
{
    public function __construct(
        public string $measurementId,
        public string $contractId,
        public MeasurementSource $source,
        public string $queryOrCounterIdentity,
        public string $runToken,
        public string $sourceSnapshotId,
        public string $targetSnapshotId,
        public string $expectedEquation,
        public string $observedValue,
        public string $difference,
        public string $tolerance,
        public string $classification,
        public string $integritySeal,
    ) {}

    /** @return list<string> */
    public function sealMaterial(): array
    {
        return [
            $this->measurementId, $this->contractId, $this->source->value, $this->queryOrCounterIdentity,
            $this->runToken, $this->sourceSnapshotId, $this->targetSnapshotId, $this->expectedEquation,
            $this->observedValue, $this->difference, $this->tolerance, $this->classification,
        ];
    }
}
