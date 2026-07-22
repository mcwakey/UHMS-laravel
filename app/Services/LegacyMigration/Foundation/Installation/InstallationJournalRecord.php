<?php

namespace App\Services\LegacyMigration\Foundation\Installation;

final readonly class InstallationJournalRecord
{
    public function __construct(
        public string $installationVersion,
        public string $targetIdentityReference,
        public string $objectCoordinate,
        public string $expectedDefinitionHash,
        public ?string $observedDefinitionHash,
        public string $state,
        public int $attempt,
        public string $auditReference,
    ) {}
}
