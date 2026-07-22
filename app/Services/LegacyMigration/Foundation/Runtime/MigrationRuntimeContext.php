<?php

namespace App\Services\LegacyMigration\Foundation\Runtime;

use Throwable;

final class MigrationRuntimeContext
{
    private ?MigrationRuntimeRequest $active = null;

    public function __construct(
        private readonly SideEffectIsolationDriver $driver,
        private readonly SideEffectIsolationRegistry $registry,
        private readonly SideEffectCounter $counter,
        private readonly MigrationRuntimeAudit $audit,
        private readonly ApplicationIsolationBootCapability $bootCapability,
        private readonly RuntimeExecutionBoundary $executionBoundary = new PhpSapiRuntimeExecutionBoundary,
    ) {}

    public function active(): bool
    {
        return $this->active !== null;
    }

    public function current(): ?MigrationRuntimeRequest
    {
        return $this->active;
    }

    public function assertActive(string $runToken, string $targetSnapshotId): MigrationRuntimeRequest
    {
        if ($this->active === null
            || ! hash_equals($this->active->runToken(), $runToken)
            || ! hash_equals($this->active->targetSnapshotId(), $targetSnapshotId)) {
            throw new RuntimeIsolationException('FOUNDATION_RUNTIME_CONTEXT_REQUIRED', 'An active matching migration runtime context is required.');
        }

        return $this->active;
    }

    public function run(MigrationRuntimeRequest $request, callable $operation): mixed
    {
        if ($this->active !== null) {
            throw new RuntimeIsolationException('FOUNDATION_RUNTIME_REENTRANT', 'Nested migration runtime contexts are forbidden.');
        }

        if (! $this->executionBoundary->isConsole()) {
            throw new RuntimeIsolationException('FOUNDATION_RUNTIME_CONSOLE_REQUIRED', 'Migration isolation may only be activated by an explicit console execution boundary.');
        }
        // Barrier state is external and mutable: boot-time evidence alone is
        // insufficient, so every activation obtains a fresh direct observation.
        $this->bootCapability->assertFresh();
        $request->assertCanActivate();
        if ($request->mode() === ExecutionMode::Commit && ! $this->driver instanceof ApplicationSideEffectIsolationDriver) {
            throw new RuntimeIsolationException('FOUNDATION_COMMIT_ISOLATION_DRIVER_REQUIRED', 'Commit requires the concrete application isolation driver with a complete control set.');
        }
        $auditSession = $this->audit->start($request);
        $subsystems = $this->registry->all();
        $baseline = $this->driver->capture($subsystems);
        $before = $this->counter->snapshot();
        $result = null;
        $failure = null;

        try {
            $this->driver->isolate($subsystems);
            foreach ($subsystems as $subsystem) {
                if (! $this->driver->isIsolated($subsystem)) {
                    throw new RuntimeIsolationException('FOUNDATION_ISOLATION_UNPROVEN', 'A prohibited subsystem remained active.');
                }
            }
            $this->audit->record($auditSession, 'ISOLATION_ACTIVATED', ['subsystem_count' => count($subsystems)]);

            $this->active = $request;
            try {
                $result = $operation($this);
            } catch (Throwable $exception) {
                $failure = $exception;
                $this->audit->record($auditSession, 'OPERATION_FAILED', ['failure_class' => $exception::class]);
            } finally {
                $this->active = null;
            }

            $attempts = $this->counter->delta($before);
            if ($attempts !== []) {
                $this->audit->record($auditSession, 'SIDE_EFFECT_DENIED', ['attempts' => $attempts]);
                if ($failure === null) {
                    $failure = new RuntimeIsolationException('FOUNDATION_SIDE_EFFECT_ATTEMPTED', 'A prohibited side effect was attempted during migration execution.');
                }
            }
        } catch (Throwable $exception) {
            $failure ??= $exception;
        } finally {
            $this->active = null;
            try {
                $this->driver->restore($baseline);
                if (! $this->driver->matches($baseline)) {
                    throw new RuntimeIsolationException('FOUNDATION_ISOLATION_RESTORE_UNPROVEN', 'The prior subsystem state was not restored exactly.');
                }
                $this->audit->record($auditSession, 'RESTORATION_SUCCEEDED');
            } catch (Throwable $restoreFailure) {
                try {
                    $this->audit->record($auditSession, 'RESTORATION_FAILED', ['failure_class' => $restoreFailure::class]);
                } catch (Throwable $auditFailure) {
                    $restoreFailure = $auditFailure;
                }
                $failure = new RuntimeIsolationException(
                    'FOUNDATION_ISOLATION_RESTORE_FAILED',
                    'Migration isolation restoration failed closed.',
                    $restoreFailure,
                );
            }
        }

        if ($failure !== null) {
            throw $failure;
        }

        return $result;
    }
}
