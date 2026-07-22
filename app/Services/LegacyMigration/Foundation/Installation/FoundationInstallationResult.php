<?php

namespace App\Services\LegacyMigration\Foundation\Installation;

final readonly class FoundationInstallationResult
{
    public function __construct(
        public string $targetIdentityReference,
        public array $manifestHashes,
        public int $plannedOperations,
        public bool $partialPriorInstallation,
    ) {}
}
