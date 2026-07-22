<?php

namespace App\Services\LegacyMigration\Foundation\Installation;

use App\Services\LegacyMigration\Foundation\Environment\IdentityBoundDdlGate;
use App\Services\LegacyMigration\Foundation\Environment\PhysicalServerIdentityVerification;
use RuntimeException;

final readonly class FoundationInstallationJournalBootstrapper
{
    public function __construct(
        private MariaDbFoundationDdlManifest $manifest,
        private FoundationDdlInspector $inspector,
        private MariaDbFoundationDdlObserver $observer,
        private MariaDbFoundationDdlOperationExecutor $executor,
        private IdentityBoundDdlGate $gate,
    ) {}

    public function ensure(
        PhysicalServerIdentityVerification $identity,
        InstallationSessionCapability $session,
        FoundationInstallationJournal $journal,
        string $auditReference,
    ): void {
        $this->gate->assertVerified($identity);
        $expected = array_values(array_filter(
            $this->manifest->expectations(),
            static fn (DdlObjectExpectation $item): bool => ($item->type === DdlObjectType::Table
                && $item->name === 'legacy_migration_installation_journal')
                || ($item->type === DdlObjectType::Trigger && str_starts_with($item->name, 'lm_install_journal_')),
        ));
        if (count($expected) !== 3) {
            throw new RuntimeException('The immutable DDL manifest has no complete installation-journal bootstrap.');
        }
        $coordinates = array_map(static fn (DdlObjectExpectation $item): string => $item->coordinate(), $expected);
        $inspection = $this->inspector->inspect($expected, array_values(array_filter(
            $this->observer->observe(),
            static fn (DdlObjectObservation $item): bool => in_array($item->coordinate(), $coordinates, true),
        )));
        if (count(array_filter($inspection, static fn (DdlInspectionResult $item): bool => $item->state === DdlInspectionState::Matching)) === count($inspection)) {
            return;
        }
        foreach ($inspection as $item) {
            if ($item->state === DdlInspectionState::Matching) {
                continue;
            }
            if ($item->state !== DdlInspectionState::Absent) {
                throw new RuntimeException('The installation-journal bootstrap is drifted or conflicting.');
            }
            $this->gate->execute(
                $identity,
                fn () => $this->executor->execute(MariaDbFoundationDdlManifest::VERSION, $item->expected->installOperationId, $identity, $this->gate, $session),
            );
        }
        $after = $this->inspector->inspect($expected, array_values(array_filter(
            $this->observer->observe(),
            static fn (DdlObjectObservation $item): bool => in_array($item->coordinate(), $coordinates, true),
        )));
        if (count(array_filter($after, static fn (DdlInspectionResult $item): bool => $item->state === DdlInspectionState::Matching)) !== count($after)) {
            throw new RuntimeException('The installation-journal bootstrap postcondition failed.');
        }
        $journal->append(new InstallationJournalRecord(
            MariaDbFoundationDdlManifest::VERSION,
            $identity->approvedIdentityReference,
            'bootstrap:legacy_migration_installation_journal',
            hash('sha256', implode('|', array_map(static fn (DdlObjectExpectation $item): string => $item->definitionHash, $expected))),
            hash('sha256', implode('|', array_map(static fn (DdlInspectionResult $item): string => (string) $item->observedDefinitionHash, $after))),
            'verified',
            1,
            $auditReference,
        ));
    }
}
