<?php

namespace App\Services\LegacyMigration\Foundation\Installation;

use Illuminate\Database\ConnectionInterface;
use RuntimeException;

final readonly class LaravelFoundationInstallationJournal implements FoundationInstallationJournal
{
    public function __construct(private ConnectionInterface $connection) {}

    public function nextAttempt(string $installationVersion, string $targetIdentityReference, string $objectCoordinate): int
    {
        $maximum = $this->connection->table('legacy_migration_installation_journal')
            ->where('installation_version', $installationVersion)
            ->where('target_identity_reference', $targetIdentityReference)
            ->where('object_coordinate', $objectCoordinate)
            ->max('attempt');

        return ((int) $maximum) + 1;
    }

    public function append(InstallationJournalRecord $record): void
    {
        foreach ([$record->targetIdentityReference, $record->expectedDefinitionHash, $record->auditReference] as $digest) {
            if (preg_match('/\A[a-f0-9]{64}\z/', $digest) !== 1) {
                throw new RuntimeException('Installation journal rejected an invalid protected reference.');
            }
        }
        if ($record->observedDefinitionHash !== null
            && preg_match('/\A[a-f0-9]{64}\z/', $record->observedDefinitionHash) !== 1) {
            throw new RuntimeException('Installation journal rejected an invalid observed definition.');
        }
        if (! in_array($record->state, ['started', 'verified', 'adopted', 'failed_closed'], true)
            || $record->attempt < 1) {
            throw new RuntimeException('Installation journal rejected an invalid state.');
        }

        $this->connection->table('legacy_migration_installation_journal')->insert([
            'installation_version' => $record->installationVersion,
            'target_identity_reference' => $record->targetIdentityReference,
            'object_coordinate' => $record->objectCoordinate,
            'expected_definition_hash' => $record->expectedDefinitionHash,
            'observed_definition_hash' => $record->observedDefinitionHash,
            'state' => $record->state,
            'attempt' => $record->attempt,
            'audit_reference' => $record->auditReference,
            'created_at' => now(),
        ]);
    }

    public function hasSuccessfulVerification(
        string $installationVersion,
        string $targetIdentityReference,
        string $objectCoordinate,
        string $expectedDefinitionHash,
    ): bool {
        return $this->connection->table('legacy_migration_installation_journal')
            ->where('installation_version', $installationVersion)
            ->where('target_identity_reference', $targetIdentityReference)
            ->where('object_coordinate', $objectCoordinate)
            ->where('expected_definition_hash', $expectedDefinitionHash)
            ->whereIn('state', ['verified', 'adopted'])
            ->exists();
    }
}
