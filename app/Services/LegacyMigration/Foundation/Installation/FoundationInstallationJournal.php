<?php

namespace App\Services\LegacyMigration\Foundation\Installation;

interface FoundationInstallationJournal
{
    public function nextAttempt(string $installationVersion, string $targetIdentityReference, string $objectCoordinate): int;

    public function append(InstallationJournalRecord $record): void;

    public function hasSuccessfulVerification(
        string $installationVersion,
        string $targetIdentityReference,
        string $objectCoordinate,
        string $expectedDefinitionHash,
    ): bool;
}
