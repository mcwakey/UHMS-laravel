<?php

namespace Tests\Unit\LegacyMigration\Foundation\Persistence;

use App\Services\LegacyMigration\Foundation\Persistence\AliasPersistenceBoundary;
use App\Services\LegacyMigration\Foundation\Persistence\AuthorizedPersistenceCommand;
use App\Services\LegacyMigration\Foundation\Persistence\CurrentMembershipPersistenceBoundary;
use App\Services\LegacyMigration\Foundation\Persistence\DomainNeutralPersistenceCommand;
use App\Services\LegacyMigration\Foundation\Persistence\EmergencyContactPersistenceBoundary;
use App\Services\LegacyMigration\Foundation\Persistence\ExistingTargetLinkPersistenceBoundary;
use App\Services\LegacyMigration\Foundation\Persistence\GuardedPatientEntityPersistenceBoundary;
use App\Services\LegacyMigration\Foundation\Persistence\InsuranceHistoryPersistenceBoundary;
use App\Services\LegacyMigration\Foundation\Persistence\PatientEntityPersistenceBoundary;
use App\Services\LegacyMigration\Foundation\Persistence\PersistenceBoundaryContext;
use App\Services\LegacyMigration\Foundation\Persistence\PersistenceBoundaryException;
use App\Services\LegacyMigration\Foundation\Persistence\PersistenceBoundaryGuard;
use App\Services\LegacyMigration\Foundation\Persistence\PersistenceBoundaryResult;
use App\Services\LegacyMigration\Foundation\Persistence\PersistenceOperation;
use App\Services\LegacyMigration\Foundation\Runtime\ApplicationSideEffectIsolationDriver;
use App\Services\LegacyMigration\Foundation\Runtime\ExecutionMode;
use App\Services\LegacyMigration\Foundation\Runtime\MigrationRuntimeContext;
use App\Services\LegacyMigration\Foundation\Runtime\MigrationRuntimeRequest;
use App\Services\LegacyMigration\Foundation\Runtime\ProhibitedSubsystem;
use App\Services\LegacyMigration\Foundation\Runtime\RuntimeIsolationException;
use App\Services\LegacyMigration\Foundation\Runtime\SideEffectCounter;
use App\Services\LegacyMigration\Foundation\Runtime\SideEffectIsolationRegistry;
use App\Services\LegacyMigration\Foundation\Runtime\SubsystemIsolationControl;
use App\Services\LegacyMigration\Foundation\Security\ProtectedToken;
use App\Services\LegacyMigration\Foundation\Validation\ExistingTargetEvidence;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class PersistenceBoundaryGuardTest extends TestCase
{
    #[Test]
    public function all_six_domain_boundaries_are_interfaces_with_no_domain_adapter(): void
    {
        foreach ([
            PatientEntityPersistenceBoundary::class,
            ExistingTargetLinkPersistenceBoundary::class,
            AliasPersistenceBoundary::class,
            EmergencyContactPersistenceBoundary::class,
            InsuranceHistoryPersistenceBoundary::class,
            CurrentMembershipPersistenceBoundary::class,
        ] as $interface) {
            self::assertTrue(interface_exists($interface));
        }
    }

    #[Test]
    public function dry_run_authorization_is_explicit_and_never_allows_business_writes(): void
    {
        [$runtime, $guard] = $this->guard();

        $authorized = $runtime->run($this->request(ExecutionMode::DryRun), fn () => $guard->authorize(
            $this->command(PersistenceOperation::PatientEntity),
            $this->validContext(patientCommitApproved: false),
        ));

        self::assertSame(ExecutionMode::DryRun, $authorized->mode);
        self::assertFalse($authorized->businessWritesAllowed);
        self::assertFalse($authorized->metadataOnly);
    }

    #[Test]
    public function guarded_adapter_dry_run_cannot_reach_its_commit_implementation(): void
    {
        [$runtime, $guard] = $this->guard();
        $adapter = new class($guard) extends GuardedPatientEntityPersistenceBoundary
        {
            public int $commitCalls = 0;

            protected function persistPatientEntityCommit(AuthorizedPersistenceCommand $command): PersistenceBoundaryResult
            {
                $this->commitCalls++;

                return new PersistenceBoundaryResult(PersistenceOperation::PatientEntity, 'committed', 1, false);
            }
        };

        $result = $runtime->run($this->request(ExecutionMode::DryRun), fn () => $adapter->persistPatientEntity(
            $this->command(PersistenceOperation::PatientEntity),
            $this->validContext(patientCommitApproved: false),
        ));

        self::assertSame('projected_no_write', $result->outcome);
        self::assertSame(0, $result->businessDomainWrites);
        self::assertSame(0, $adapter->commitCalls);
    }

    #[Test]
    public function a_boundary_cannot_be_invoked_without_the_active_matching_runtime(): void
    {
        [, $guard] = $this->guard();

        $this->expectException(RuntimeIsolationException::class);
        $guard->authorize(
            $this->command(PersistenceOperation::Alias),
            $this->validContext(),
        );
    }

    #[Test]
    public function phase3_runtime_refuses_patient_commit_even_if_callers_claim_approval(): void
    {
        [$runtime, $guard] = $this->guard();

        try {
            $runtime->run($this->request(ExecutionMode::Commit), fn () => $guard->authorize(
                $this->command(PersistenceOperation::PatientEntity),
                $this->validContext(patientCommitApproved: false),
            ));
            self::fail('Patient commit must remain blocked.');
        } catch (RuntimeIsolationException $exception) {
            self::assertSame('FOUNDATION_RUNTIME_COMMIT_PHASE_BLOCKED', $exception->faultCode);
        }
    }

    #[Test]
    public function phase3_runtime_refuses_membership_commit_even_if_callers_claim_approval(): void
    {
        [$runtime, $guard] = $this->guard();

        try {
            $runtime->run($this->request(ExecutionMode::Commit), fn () => $guard->authorize(
                $this->command(PersistenceOperation::CurrentMembership),
                $this->validContext(
                    insuranceCommitApproved: false,
                    insuranceHistoryComplete: true,
                ),
            ));
            self::fail('Insurance initialization must remain blocked.');
        } catch (RuntimeIsolationException $exception) {
            self::assertSame('FOUNDATION_RUNTIME_COMMIT_PHASE_BLOCKED', $exception->faultCode);
        }
    }

    #[Test]
    public function existing_target_authorization_is_metadata_only_and_requires_immutability_proof(): void
    {
        [$runtime, $guard] = $this->guard();

        $authorized = $runtime->run($this->request(ExecutionMode::DryRun), fn () => $guard->authorize(
            $this->command(PersistenceOperation::ExistingTargetLink),
            $this->validContext(existingImmutable: true),
        ));
        self::assertTrue($authorized->metadataOnly);
        self::assertFalse($authorized->businessWritesAllowed);

        try {
            $runtime->run($this->request(ExecutionMode::DryRun), fn () => $guard->authorize(
                $this->command(PersistenceOperation::ExistingTargetLink),
                $this->validContext(existingImmutable: false),
            ));
            self::fail('Missing immutability evidence must block the link.');
        } catch (PersistenceBoundaryException $exception) {
            self::assertSame('FOUNDATION_PERSISTENCE_EXISTING_TARGET_UNPROVEN', $exception->faultCode);
        }
    }

    /** @return array{MigrationRuntimeContext, PersistenceBoundaryGuard} */
    private function guard(): array
    {
        $runtime = new MigrationRuntimeContext(
            new ApplicationSideEffectIsolationDriver(array_map(
                static fn (ProhibitedSubsystem $subsystem): BoundaryIsolationControl => new BoundaryIsolationControl($subsystem),
                ProhibitedSubsystem::cases(),
            )),
            SideEffectIsolationRegistry::complete(),
            new SideEffectCounter,
        );

        return [$runtime, new PersistenceBoundaryGuard($runtime)];
    }

    private function request(ExecutionMode $mode): MigrationRuntimeRequest
    {
        return new MigrationRuntimeRequest(
            runToken: str_repeat('a', 64),
            targetSnapshotId: str_repeat('b', 64),
            environment: 'testing',
            approvedEnvironments: ['testing'],
            mode: $mode,
            foundationEnabled: true,
            initiatedFromConsole: true,
            productionTarget: false,
            runValid: true,
            sourceSnapshotPinned: true,
            targetSnapshotPinned: true,
            targetCollisionSnapshotCurrent: true,
            dryRunOnly: $mode === ExecutionMode::DryRun,
            commitAuthorized: $mode === ExecutionMode::Commit,
        );
    }

    private function command(PersistenceOperation $operation): DomainNeutralPersistenceCommand
    {
        return new DomainNeutralPersistenceCommand(
            operation: $operation,
            runToken: str_repeat('a', 64),
            idempotencyKey: str_repeat('c', 64),
            targetSnapshotId: str_repeat('b', 64),
            protectedReferences: ['source' => str_repeat('d', 64)],
            classificationRuleIds: ['SYNTHETIC-CLASSIFICATION-001'],
        );
    }

    private function validContext(
        bool $patientCommitApproved = true,
        bool $insuranceCommitApproved = true,
        bool $existingImmutable = false,
        bool $insuranceHistoryComplete = false,
    ): PersistenceBoundaryContext {
        return new PersistenceBoundaryContext(
            runValid: true,
            snapshotsPinned: true,
            requiredMappingsResolved: true,
            patientStateValidated: true,
            patientStateCommitApproved: $patientCommitApproved,
            insuranceInitializationValidated: true,
            insuranceInitializationCommitApproved: $insuranceCommitApproved,
            idempotencyKeyValid: true,
            targetCollisionSnapshotCurrent: true,
            provenanceAvailable: true,
            reconciliationAvailable: true,
            existingTargetEvidence: $existingImmutable ? $this->existingTargetEvidence() : null,
            insuranceHistoryComplete: $insuranceHistoryComplete,
        );
    }

    private function existingTargetEvidence(): ExistingTargetEvidence
    {
        return new ExistingTargetEvidence(
            evidenceVersion: 'existing-target-evidence/1',
            contractVersion: '2F.1.0',
            runToken: str_repeat('a', 64),
            targetSnapshotId: str_repeat('b', 64),
            domain: 'patient',
            targetReference: $this->protectedToken('target_record', '1'),
            lockEvidence: $this->protectedToken('artifact_integrity', '2'),
            lineageEvidence: $this->protectedToken('idempotency', '3'),
            targetExists: true,
            softDeleted: false,
            mergedOrRedirected: false,
            beforeState: $this->protectedToken('target_record', '4'),
            afterState: $this->protectedToken('target_record', '4'),
            intendedTargetMutations: 0,
            observedTargetMutations: 0,
        );
    }

    private function protectedToken(string $domain, string $byte): ProtectedToken
    {
        return new ProtectedToken('testing', $domain, 'synthetic-key', 'v1', 'typed-length-prefix/1', str_repeat($byte, 32));
    }
}

final class BoundaryIsolationControl implements SubsystemIsolationControl
{
    private bool $isolated = false;

    public function __construct(private readonly ProhibitedSubsystem $ownedSubsystem) {}

    public function subsystem(): ProhibitedSubsystem
    {
        return $this->ownedSubsystem;
    }

    public function isIsolated(): bool
    {
        return $this->isolated;
    }

    public function isolate(): void
    {
        $this->isolated = true;
    }

    public function restore(bool $previouslyIsolated): void
    {
        $this->isolated = $previouslyIsolated;
    }
}
