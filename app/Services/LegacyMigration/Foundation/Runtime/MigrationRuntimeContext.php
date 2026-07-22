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
            || ! hash_equals($this->active->runToken, $runToken)
            || ! hash_equals($this->active->targetSnapshotId, $targetSnapshotId)) {
            throw new RuntimeIsolationException('FOUNDATION_RUNTIME_CONTEXT_REQUIRED', 'An active matching migration runtime context is required.');
        }

        return $this->active;
    }

    public function run(MigrationRuntimeRequest $request, callable $operation): mixed
    {
        if ($this->active !== null) {
            throw new RuntimeIsolationException('FOUNDATION_RUNTIME_REENTRANT', 'Nested migration runtime contexts are forbidden.');
        }

        $request->assertCanActivate();
        if ($request->mode === ExecutionMode::Commit && ! $this->driver instanceof ApplicationSideEffectIsolationDriver) {
            throw new RuntimeIsolationException('FOUNDATION_COMMIT_ISOLATION_DRIVER_REQUIRED', 'Commit requires the concrete application isolation driver with a complete control set.');
        }
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

            $this->active = $request;
            try {
                $result = $operation($this);
            } catch (Throwable $exception) {
                $failure = $exception;
            } finally {
                $this->active = null;
            }

            if ($this->counter->delta($before) !== [] && $failure === null) {
                $failure = new RuntimeIsolationException('FOUNDATION_SIDE_EFFECT_ATTEMPTED', 'A prohibited side effect was attempted during migration execution.');
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
            } catch (Throwable $restoreFailure) {
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
