<?php

namespace App\Services\LegacyMigration\Foundation\Runtime;

final class MigrationRuntimeFactory
{
    public function __construct(private readonly ApplicationIsolationBootCapability $bootCapability) {}

    /**
     * This is the only application binding path. It fails unless configuration
     * and one observable real control per subsystem are complete.
     *
     * @param  array<string, mixed>  $configuration
     * @param  iterable<SubsystemIsolationControl>  $controls
     */
    public function create(
        array $configuration,
        iterable $controls,
        MigrationRuntimeAudit $audit,
        ?SideEffectCounter $counter = null,
        RuntimeExecutionBoundary $executionBoundary = new PhpSapiRuntimeExecutionBoundary,
    ): ApplicationMigrationRuntime {
        $this->bootCapability->assertFresh();
        $registry = SideEffectIsolationRegistry::fromConfiguration($configuration);
        $counter ??= new SideEffectCounter;
        $context = new MigrationRuntimeContext(
            new ApplicationSideEffectIsolationDriver($controls),
            $registry,
            $counter,
            $audit,
            $this->bootCapability,
            $executionBoundary,
        );

        return new ApplicationMigrationRuntime(
            context: $context,
            sideEffects: new SideEffectGuard($context, $registry, $counter),
            registry: $registry,
        );
    }
}
