<?php

namespace App\Services\LegacyMigration\Foundation\Allocation;

final readonly class AllocationResult
{
    private function __construct(
        public string $action,
        public ?string $number,
        public ?int $sequenceOrdinal,
        public string $configurationFingerprint,
        public string $periodKey,
        public bool $binding,
        public bool $sequenceMutated,
    ) {}

    public static function symbolic(PinnedNumberingConfiguration $configuration): self
    {
        return new self(
            'TARGET_GENERATED_AT_COMMIT',
            null,
            null,
            $configuration->fingerprint(),
            $configuration->periodKey,
            false,
            false,
        );
    }

    public static function reserved(string $number, int $ordinal, PinnedNumberingConfiguration $configuration): self
    {
        return new self('RESERVED_FOR_ATOMIC_UNIT', $number, $ordinal, $configuration->fingerprint(), $configuration->periodKey, true, true);
    }

    public static function reused(ExistingAllocation $allocation): self
    {
        return new self('REUSE_VERIFIED_PRIOR_MIGRATION_ALLOCATION', $allocation->number, $allocation->sequenceOrdinal, $allocation->configurationFingerprint, $allocation->periodKey, true, false);
    }
}
