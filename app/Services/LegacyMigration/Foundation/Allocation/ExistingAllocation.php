<?php

namespace App\Services\LegacyMigration\Foundation\Allocation;

final readonly class ExistingAllocation
{
    public function __construct(
        public string $protectedSourceToken,
        public string $patientCoreKey,
        public string $configurationFingerprint,
        public string $periodKey,
        public string $number,
        public int $sequenceOrdinal,
        public bool $successful,
    ) {}

    public function assertCompatible(AllocationRequest $request): void
    {
        if (! $this->successful
            || ! hash_equals($this->protectedSourceToken, $request->protectedSourceToken)
            || ! hash_equals($this->patientCoreKey, $request->patientCoreKey)
            || ! hash_equals($this->configurationFingerprint, $request->configuration->fingerprint())
            || $this->periodKey !== $request->configuration->periodKey) {
            throw AllocationException::failClosed('LEGACY-PATIENT-NUMBER-038');
        }
    }
}
