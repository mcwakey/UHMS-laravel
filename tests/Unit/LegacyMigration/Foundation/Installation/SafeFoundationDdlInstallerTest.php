<?php

namespace Tests\Unit\LegacyMigration\Foundation\Installation;

use App\Services\LegacyMigration\Foundation\Environment\HmacIdentityReferenceHasher;
use App\Services\LegacyMigration\Foundation\Environment\IdentityBoundDdlGate;
use App\Services\LegacyMigration\Foundation\Environment\PhysicalServerIdentityVerification;
use App\Services\LegacyMigration\Foundation\Installation\DdlInspectionException;
use App\Services\LegacyMigration\Foundation\Installation\DdlInspectionResult;
use App\Services\LegacyMigration\Foundation\Installation\DdlInspectionState;
use App\Services\LegacyMigration\Foundation\Installation\DdlObjectExpectation;
use App\Services\LegacyMigration\Foundation\Installation\DdlObjectObservation;
use App\Services\LegacyMigration\Foundation\Installation\DdlObjectType;
use App\Services\LegacyMigration\Foundation\Installation\DdlOperationExecutor;
use App\Services\LegacyMigration\Foundation\Installation\DdlRecoveryPlanner;
use App\Services\LegacyMigration\Foundation\Installation\FoundationDdlInspector;
use App\Services\LegacyMigration\Foundation\Installation\FoundationInstallationJournal;
use App\Services\LegacyMigration\Foundation\Installation\InstallationIdentityContract;
use App\Services\LegacyMigration\Foundation\Installation\InstallationJournalRecord;
use App\Services\LegacyMigration\Foundation\Installation\InstallationSessionCapability;
use App\Services\LegacyMigration\Foundation\Installation\SafeFoundationDdlInstaller;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class SafeFoundationDdlInstallerTest extends TestCase
{
    public function test_partial_matching_installation_executes_only_absent_allowlisted_objects_and_is_rerunnable(): void
    {
        $manifest = $this->manifest();
        $observed = [$this->observation($manifest[0])];
        $executor = new class($manifest, $observed) implements DdlOperationExecutor
        {
            /** @param list<DdlObjectExpectation> $manifest @param list<DdlObjectObservation> $observed */
            public function __construct(private array $manifest, public array $observed) {}

            public array $executed = [];

            public function execute(string $version, string $operationId, PhysicalServerIdentityVerification $identity, IdentityBoundDdlGate $gate, InstallationSessionCapability $session): void
            {
                $this->executed[] = $operationId;
                foreach ($this->manifest as $item) {
                    if ($item->installOperationId === $operationId) {
                        $this->observed[] = new DdlObjectObservation($item->type, $item->name, $item->definitionHash);
                    }
                }
            }
        };
        $journal = $this->journal();
        $installer = new SafeFoundationDdlInstaller(new FoundationDdlInspector, new DdlRecoveryPlanner, $executor, $journal, $this->gate());
        $observe = fn (): array => $executor->observed;

        $audit = hash('sha256', 'audit');
        $plan = $installer->install($this->identity(), $this->session($audit), 'P3B-DDL-1', $manifest, $observe, $audit);
        $this->assertTrue($plan->partialPriorInstallation);
        $this->assertSame(['create-trigger'], $executor->executed);
        $this->assertSame(['adopted', 'started', 'verified'], array_column($journal->records, 'state'));

        $rerunAudit = hash('sha256', 'audit-rerun');
        $rerun = $installer->install($this->identity(), $this->session($rerunAudit), 'P3B-DDL-1', $manifest, $observe, $rerunAudit);
        $this->assertTrue($rerun->completed);
        $this->assertSame(['create-trigger'], $executor->executed);
    }

    public function test_drifted_expected_object_fails_without_execution_or_repair(): void
    {
        $manifest = $this->manifest();
        $executor = new class implements DdlOperationExecutor
        {
            public bool $called = false;

            public function execute(string $version, string $operationId, PhysicalServerIdentityVerification $identity, IdentityBoundDdlGate $gate, InstallationSessionCapability $session): void
            {
                $this->called = true;
            }
        };
        $installer = new SafeFoundationDdlInstaller(new FoundationDdlInspector, new DdlRecoveryPlanner, $executor, $this->journal(), $this->gate());

        try {
            $audit = hash('sha256', 'audit');
            $installer->install($this->identity(), $this->session($audit), 'P3B-DDL-1', $manifest, fn (): array => [
                new DdlObjectObservation($manifest[0]->type, $manifest[0]->name, hash('sha256', 'drift')),
            ], $audit);
            $this->fail('Drifted DDL was accepted.');
        } catch (RuntimeException $exception) {
            $this->assertStringContainsString('not automatically repairable', $exception->getMessage());
            $this->assertFalse($executor->called);
        }
    }

    public function test_unexpected_reserved_object_is_a_nonrepairable_conflict(): void
    {
        try {
            (new FoundationDdlInspector)->inspect($this->manifest(), [
                new DdlObjectObservation(DdlObjectType::Table, 'legacy_migration_unknown', hash('sha256', 'unknown')),
            ]);
            $this->fail('Conflicting reserved DDL object was accepted.');
        } catch (DdlInspectionException $exception) {
            $this->assertSame(DdlInspectionState::Conflicting, $exception->state);
        }
    }

    public function test_metadata_gap_is_explicitly_repairable_but_version_bound(): void
    {
        $expected = new DdlObjectExpectation('P3B-DDL-1', DdlObjectType::MigrationLedger, 'legacy_migration_installation_ledger', hash('sha256', 'ledger'), 'record-ledger', true);
        $result = (new FoundationDdlInspector)->inspect([$expected], []);
        $this->assertSame(DdlInspectionState::RepairableMetadataGap, $result[0]->state);
        $this->assertCount(1, (new DdlRecoveryPlanner)->plan('P3B-DDL-1', $result)->operations);
    }

    public function test_supported_absent_objects_are_planned_but_index_or_constraint_definition_drift_is_refused(): void
    {
        foreach ([DdlObjectType::Table, DdlObjectType::Column, DdlObjectType::Trigger, DdlObjectType::MigrationLedger] as $type) {
            $name = match ($type) {
                DdlObjectType::Column => 'legacy_migration_atomic_intents.transition_attempt_count',
                DdlObjectType::Trigger => 'lm_missing_trigger',
                DdlObjectType::MigrationLedger => 'legacy_migration_ledger_synthetic',
                default => 'legacy_migration_missing_table',
            };
            $expected = new DdlObjectExpectation('P3B-DDL-1', $type, $name, hash('sha256', $name), 'install-'.$type->value, $type === DdlObjectType::MigrationLedger);
            $inspection = (new FoundationDdlInspector)->inspect([$expected], []);
            $this->assertCount(1, (new DdlRecoveryPlanner)->plan('P3B-DDL-1', $inspection)->operations);
        }

        foreach (['missing-index', 'missing-constraint'] as $drift) {
            $table = new DdlObjectExpectation('P3B-DDL-1', DdlObjectType::Table, 'legacy_migration_runs', hash('sha256', 'complete-table'), 'create-table');
            $inspection = (new FoundationDdlInspector)->inspect([$table], [
                new DdlObjectObservation(DdlObjectType::Table, $table->name, hash('sha256', $drift)),
            ]);
            $this->expectPlannerRefusal($inspection);
        }
    }

    public function test_retry_of_an_exact_statement_is_a_zero_operation_plan(): void
    {
        $expected = $this->manifest()[0];
        $inspection = (new FoundationDdlInspector)->inspect([$expected], [$this->observation($expected)]);
        $plan = (new DdlRecoveryPlanner)->plan('P3B-DDL-1', $inspection);

        $this->assertTrue($plan->completed);
        $this->assertCount(0, $plan->operations);
    }

    public function test_interrupted_operation_is_journalled_and_explicit_rerun_uses_next_attempt(): void
    {
        $manifest = [$this->manifest()[0]];
        $executor = new class($manifest[0]) implements DdlOperationExecutor
        {
            public bool $fail = true;

            /** @var list<DdlObjectObservation> */
            public array $observed = [];

            public function __construct(private readonly DdlObjectExpectation $expected) {}

            public function execute(string $version, string $operationId, PhysicalServerIdentityVerification $identity, IdentityBoundDdlGate $gate, InstallationSessionCapability $session): void
            {
                if ($this->fail) {
                    throw new RuntimeException('Synthetic interruption.');
                }
                $this->observed[] = new DdlObjectObservation(
                    $this->expected->type, $this->expected->name, $this->expected->definitionHash,
                );
            }
        };
        $journal = $this->journal();
        $installer = new SafeFoundationDdlInstaller(
            new FoundationDdlInspector, new DdlRecoveryPlanner, $executor, $journal, $this->gate(),
        );
        try {
            $audit = hash('sha256', 'audit-1');
            $installer->install($this->identity(), $this->session($audit), 'P3B-DDL-1', $manifest, fn (): array => $executor->observed, $audit);
            $this->fail('Synthetic interruption was ignored.');
        } catch (RuntimeException) {
            $this->assertSame(['started', 'failed_closed'], array_column($journal->records, 'state'));
        }

        $executor->fail = false;
        $retryAudit = hash('sha256', 'audit-2');
        $installer->install($this->identity(), $this->session($retryAudit), 'P3B-DDL-1', $manifest, fn (): array => $executor->observed, $retryAudit);
        $this->assertSame([1, 1, 2, 2], array_column($journal->records, 'attempt'));
        $this->assertSame(['started', 'failed_closed', 'started', 'verified'], array_column($journal->records, 'state'));
    }

    /** @return list<DdlObjectExpectation> */
    private function manifest(): array
    {
        return [
            new DdlObjectExpectation('P3B-DDL-1', DdlObjectType::Table, 'legacy_migration_runs', hash('sha256', 'runs-ddl'), 'create-runs'),
            new DdlObjectExpectation('P3B-DDL-1', DdlObjectType::Trigger, 'lm_runs_no_delete', hash('sha256', 'trigger-ddl'), 'create-trigger'),
        ];
    }

    private function observation(DdlObjectExpectation $expected): DdlObjectObservation
    {
        return new DdlObjectObservation($expected->type, $expected->name, $expected->definitionHash);
    }

    /** @param list<DdlInspectionResult> $inspection */
    private function expectPlannerRefusal(array $inspection): void
    {
        try {
            (new DdlRecoveryPlanner)->plan('P3B-DDL-1', $inspection);
            $this->fail('Definition drift was automatically repaired.');
        } catch (RuntimeException) {
            $this->assertTrue(true);
        }
    }

    private function identity(): PhysicalServerIdentityVerification
    {
        return PhysicalServerIdentityVerification::issue('P3B-IDENTITY-1', hash('sha256', 'identity'), hash('sha256', 'schema'), hash('sha256', 'config'), $this->hasher());
    }

    private function gate(): IdentityBoundDdlGate
    {
        return new IdentityBoundDdlGate($this->hasher());
    }

    private function session(string $auditReference): InstallationSessionCapability
    {
        $identity = $this->identity();

        return InstallationSessionCapability::issue(
            InstallationIdentityContract::issue(
                $identity->approvedIdentityReference,
                $identity->connectionInstanceReference,
                $identity->structuralIdentityReference,
                ['P3B-DDL-1' => hash('sha256', 'manifest')],
                hash('sha256', 'partial-state'),
                $this->hasher(),
            ),
            $auditReference,
            ['P3B-DDL-1'],
            $this->hasher(),
        );
    }

    private function hasher(): HmacIdentityReferenceHasher
    {
        return new HmacIdentityReferenceHasher('synthetic-disposable', 'identity-key', 'v1', str_repeat('s', 64));
    }

    private function journal(): FoundationInstallationJournal
    {
        return new class implements FoundationInstallationJournal
        {
            /** @var list<InstallationJournalRecord> */
            public array $records = [];

            public function nextAttempt(string $installationVersion, string $targetIdentityReference, string $objectCoordinate): int
            {
                $attempts = array_map(
                    static fn (InstallationJournalRecord $record): int => $record->attempt,
                    array_filter(
                        $this->records,
                        static fn (InstallationJournalRecord $record): bool => $record->installationVersion === $installationVersion
                            && $record->targetIdentityReference === $targetIdentityReference
                            && $record->objectCoordinate === $objectCoordinate,
                    ),
                );

                return $attempts === [] ? 1 : max($attempts) + 1;
            }

            public function append(InstallationJournalRecord $record): void
            {
                $this->records[] = $record;
            }

            public function hasSuccessfulVerification(string $installationVersion, string $targetIdentityReference, string $objectCoordinate, string $expectedDefinitionHash): bool
            {
                foreach ($this->records as $record) {
                    if ($record->installationVersion === $installationVersion
                        && $record->targetIdentityReference === $targetIdentityReference
                        && $record->objectCoordinate === $objectCoordinate
                        && $record->expectedDefinitionHash === $expectedDefinitionHash
                        && in_array($record->state, ['verified', 'adopted'], true)) {
                        return true;
                    }
                }

                return false;
            }
        };
    }
}
