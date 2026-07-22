<?php

namespace Tests\Unit\LegacyMigration\Foundation\Runtime;

use App\Services\LegacyMigration\Foundation\Runtime\ApplicationSideEffectIsolationDriver;
use App\Services\LegacyMigration\Foundation\Runtime\ExecutionMode;
use App\Services\LegacyMigration\Foundation\Runtime\MigrationRuntimeContext;
use App\Services\LegacyMigration\Foundation\Runtime\MigrationRuntimeFactory;
use App\Services\LegacyMigration\Foundation\Runtime\MigrationRuntimeRequest;
use App\Services\LegacyMigration\Foundation\Runtime\OutboundChannel;
use App\Services\LegacyMigration\Foundation\Runtime\ProhibitedSubsystem;
use App\Services\LegacyMigration\Foundation\Runtime\RuntimeIsolationException;
use App\Services\LegacyMigration\Foundation\Runtime\SideEffectCounter;
use App\Services\LegacyMigration\Foundation\Runtime\SideEffectGuard;
use App\Services\LegacyMigration\Foundation\Runtime\SideEffectIsolationDriver;
use App\Services\LegacyMigration\Foundation\Runtime\SideEffectIsolationRegistry;
use App\Services\LegacyMigration\Foundation\Runtime\SideEffectIsolationSnapshot;
use App\Services\LegacyMigration\Foundation\Runtime\SubsystemIsolationControl;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class MigrationRuntimeContextTest extends TestCase
{
    #[Test]
    public function it_requires_the_complete_directive_subsystem_registry(): void
    {
        $configured = array_map(static fn (ProhibitedSubsystem $item): string => $item->value, ProhibitedSubsystem::cases());
        self::assertCount(25, $configured);
        self::assertContains('payment_allocation', $configured);
        self::assertContains('external_integrations', $configured);
        self::assertNotContains('migration_audit', $configured);

        array_pop($configured);

        $this->expectException(RuntimeIsolationException::class);
        $this->expectExceptionMessage('incomplete');
        new SideEffectIsolationRegistry($configured);
    }

    #[Test]
    public function configuration_omissions_fail_closed(): void
    {
        $configured = array_map(static fn (ProhibitedSubsystem $item): string => $item->value, ProhibitedSubsystem::cases());
        $configured = array_values(array_diff($configured, ['payment_allocation', 'external_integrations']));

        $this->expectException(RuntimeIsolationException::class);
        $this->expectExceptionMessage('incomplete');
        SideEffectIsolationRegistry::fromConfiguration([
            'isolation' => [
                'required_subsystems' => $configured,
                'outbound_deny_list' => array_map(static fn (OutboundChannel $channel): string => $channel->value, OutboundChannel::cases()),
            ],
        ]);
    }

    #[Test]
    public function runtime_registry_matches_the_machine_readable_deny_list(): void
    {
        $specificationPath = dirname(__DIR__, 5).'/docs/legacy-migration/phase-3/specifications/side_effect_deny_list.json';
        $specification = json_decode((string) file_get_contents($specificationPath), true, flags: JSON_THROW_ON_ERROR);
        $expected = $specification['subsystems'];
        $actual = array_map(static fn (ProhibitedSubsystem $item): string => $item->value, ProhibitedSubsystem::cases());
        sort($expected, SORT_STRING);
        sort($actual, SORT_STRING);

        self::assertSame($expected, $actual);
    }

    #[Test]
    public function it_is_console_only_non_production_and_restores_after_success(): void
    {
        $driver = new FakeIsolationDriver;
        [$context] = $this->runtime($driver);

        $result = $context->run($this->request(), function (MigrationRuntimeContext $active): string {
            self::assertTrue($active->active());

            return 'completed';
        });

        self::assertSame('completed', $result);
        self::assertFalse($context->active());
        self::assertSame(1, $driver->restoreCalls);
        self::assertFalse(in_array(true, $driver->state, true));

        $this->expectException(RuntimeIsolationException::class);
        $context->run($this->request(environment: 'production'), static fn (): null => null);
    }

    #[Test]
    public function it_denies_and_counts_attempts_but_leaves_normal_runtime_unchanged(): void
    {
        $driver = new FakeIsolationDriver;
        [$context, $guard, $counter] = $this->runtime($driver);

        $guard->assertAllowed(ProhibitedSubsystem::Notifications);
        self::assertSame([], $counter->snapshot());

        try {
            $context->run($this->request(), function () use ($guard): void {
                $guard->assertAllowed(ProhibitedSubsystem::BillingCreation);
            });
            self::fail('A prohibited side effect should abort the scope.');
        } catch (RuntimeIsolationException $exception) {
            self::assertSame('FOUNDATION_SIDE_EFFECT_DENIED', $exception->faultCode);
        }

        self::assertSame(['billing' => 1], $counter->snapshot());
        self::assertFalse($context->active());
        self::assertSame(1, $driver->restoreCalls);
    }

    #[DataProvider('prohibitedSubsystems')]
    #[Test]
    public function every_directive_subsystem_is_individually_denied(ProhibitedSubsystem $subsystem): void
    {
        $driver = new FakeIsolationDriver;
        [$context, $guard, $counter] = $this->runtime($driver);

        try {
            $context->run($this->request(), function () use ($guard, $subsystem): void {
                $guard->assertAllowed($subsystem);
            });
            self::fail('The directive subsystem should be denied.');
        } catch (RuntimeIsolationException $exception) {
            self::assertSame('FOUNDATION_SIDE_EFFECT_DENIED', $exception->faultCode);
        }

        self::assertSame(1, $counter->snapshot()[$subsystem->value] ?? 0);
        self::assertFalse($context->active());
        self::assertSame(1, $driver->restoreCalls);
    }

    #[DataProvider('outboundChannels')]
    #[Test]
    public function every_outbound_channel_is_individually_denied(OutboundChannel $channel): void
    {
        $driver = new FakeIsolationDriver;
        [$context, $guard, $counter] = $this->runtime($driver);

        try {
            $context->run($this->request(), function () use ($guard, $channel): void {
                $guard->assertOutboundAllowed($channel);
            });
            self::fail('The outbound channel should be denied.');
        } catch (RuntimeIsolationException $exception) {
            self::assertSame('FOUNDATION_OUTBOUND_DENIED', $exception->faultCode);
        }

        self::assertSame(1, $counter->snapshot()[$channel->subsystem()->value] ?? 0);
        self::assertFalse($context->active());
        self::assertSame(1, $driver->restoreCalls);
    }

    #[Test]
    public function it_restores_after_callback_failure_and_fails_closed_when_restoration_is_unproved(): void
    {
        $driver = new FakeIsolationDriver;
        [$context] = $this->runtime($driver);

        try {
            $context->run($this->request(), static function (): void {
                throw new RuntimeException('synthetic callback failure');
            });
            self::fail('The callback failure should escape.');
        } catch (RuntimeException $exception) {
            self::assertSame('synthetic callback failure', $exception->getMessage());
        }
        self::assertFalse($context->active());
        self::assertSame(1, $driver->restoreCalls);

        $driver->failRestoreProof = true;
        try {
            $context->run($this->request(), static fn (): string => 'ignored');
            self::fail('Unproved restoration must fail closed.');
        } catch (RuntimeIsolationException $exception) {
            self::assertSame('FOUNDATION_ISOLATION_RESTORE_FAILED', $exception->faultCode);
        }
        self::assertFalse($context->active());
    }

    #[Test]
    public function interface_only_drivers_can_never_authorize_commit(): void
    {
        $driver = new FakeIsolationDriver;
        [$context] = $this->runtime($driver);
        $request = new MigrationRuntimeRequest(
            runToken: str_repeat('a', 64),
            targetSnapshotId: str_repeat('b', 64),
            environment: 'testing',
            approvedEnvironments: ['testing'],
            mode: ExecutionMode::Commit,
            foundationEnabled: true,
            initiatedFromConsole: true,
            productionTarget: false,
            runValid: true,
            sourceSnapshotPinned: true,
            targetSnapshotPinned: true,
            targetCollisionSnapshotCurrent: true,
            dryRunOnly: false,
            commitAuthorized: true,
        );

        try {
            $context->run($request, static fn (): null => null);
            self::fail('An interface-only driver must never authorize commit.');
        } catch (RuntimeIsolationException $exception) {
            self::assertSame('FOUNDATION_RUNTIME_COMMIT_PHASE_BLOCKED', $exception->faultCode);
        }
    }

    #[Test]
    public function concrete_driver_attempts_every_restoration_after_an_individual_control_fails(): void
    {
        $controls = array_map(
            static fn (ProhibitedSubsystem $subsystem): TrackingIsolationControl => new TrackingIsolationControl($subsystem),
            ProhibitedSubsystem::cases(),
        );
        foreach ($controls as $control) {
            if ($control->subsystem() === ProhibitedSubsystem::OperationalActivityLog) {
                $control->failRestore = true;
            }
        }
        $laterControl = array_values(array_filter(
            $controls,
            static fn (TrackingIsolationControl $control): bool => $control->subsystem() === ProhibitedSubsystem::Webhooks,
        ))[0];
        $registry = SideEffectIsolationRegistry::complete();
        $context = new MigrationRuntimeContext(
            new ApplicationSideEffectIsolationDriver($controls),
            $registry,
            new SideEffectCounter,
        );

        try {
            $context->run($this->request(), static fn (): string => 'complete');
            self::fail('A restoration-control failure must fail the scope.');
        } catch (RuntimeIsolationException $exception) {
            self::assertSame('FOUNDATION_ISOLATION_RESTORE_FAILED', $exception->faultCode);
        }

        self::assertTrue($laterControl->restoreAttempted);
        self::assertFalse($laterControl->isIsolated());
    }

    #[Test]
    public function application_factory_fails_closed_without_real_complete_controls(): void
    {
        $this->expectException(RuntimeIsolationException::class);
        $this->expectExceptionMessage('control set is incomplete');

        (new MigrationRuntimeFactory)->create([
            'isolation' => [
                'required_subsystems' => array_map(static fn (ProhibitedSubsystem $item): string => $item->value, ProhibitedSubsystem::cases()),
                'outbound_deny_list' => array_map(static fn (OutboundChannel $item): string => $item->value, OutboundChannel::cases()),
            ],
        ], []);
    }

    /** @return array{MigrationRuntimeContext, SideEffectGuard, SideEffectCounter} */
    private function runtime(FakeIsolationDriver $driver): array
    {
        $registry = SideEffectIsolationRegistry::complete();
        $counter = new SideEffectCounter;
        $context = new MigrationRuntimeContext($driver, $registry, $counter);

        return [$context, new SideEffectGuard($context, $registry, $counter), $counter];
    }

    private function request(string $environment = 'testing'): MigrationRuntimeRequest
    {
        return new MigrationRuntimeRequest(
            runToken: str_repeat('a', 64),
            targetSnapshotId: str_repeat('b', 64),
            environment: $environment,
            approvedEnvironments: ['local', 'testing'],
            mode: ExecutionMode::DryRun,
            foundationEnabled: true,
            initiatedFromConsole: true,
            productionTarget: false,
            runValid: true,
            sourceSnapshotPinned: true,
            targetSnapshotPinned: true,
            targetCollisionSnapshotCurrent: true,
        );
    }

    /** @return array<string, array{ProhibitedSubsystem}> */
    public static function prohibitedSubsystems(): array
    {
        $cases = [];
        foreach (ProhibitedSubsystem::cases() as $subsystem) {
            $cases[$subsystem->value] = [$subsystem];
        }

        return $cases;
    }

    /** @return array<string, array{OutboundChannel}> */
    public static function outboundChannels(): array
    {
        $cases = [];
        foreach (OutboundChannel::cases() as $channel) {
            $cases[$channel->value] = [$channel];
        }

        return $cases;
    }
}

final class FakeIsolationDriver implements SideEffectIsolationDriver
{
    /** @var array<string, bool> */
    public array $state = [];

    public int $restoreCalls = 0;

    public bool $failRestoreProof = false;

    public function capture(array $subsystems): SideEffectIsolationSnapshot
    {
        foreach ($subsystems as $subsystem) {
            $this->state[$subsystem->value] ??= false;
        }

        return new SideEffectIsolationSnapshot($this->state);
    }

    public function isolate(array $subsystems): void
    {
        foreach ($subsystems as $subsystem) {
            $this->state[$subsystem->value] = true;
        }
    }

    public function isIsolated(ProhibitedSubsystem $subsystem): bool
    {
        return $this->state[$subsystem->value] ?? false;
    }

    public function restore(SideEffectIsolationSnapshot $snapshot): void
    {
        $this->restoreCalls++;
        $this->state = $snapshot->isolated;
    }

    public function matches(SideEffectIsolationSnapshot $snapshot): bool
    {
        return ! $this->failRestoreProof && $this->state === $snapshot->isolated;
    }
}

final class TrackingIsolationControl implements SubsystemIsolationControl
{
    private bool $isolated = false;

    public bool $failRestore = false;

    public bool $restoreAttempted = false;

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
        $this->restoreAttempted = true;
        if ($this->failRestore) {
            throw new RuntimeException('synthetic restoration failure');
        }
        $this->isolated = $previouslyIsolated;
    }
}
