<?php

namespace App\Services\LegacyMigration\Foundation\Persistence;

use App\Services\LegacyMigration\Foundation\Runtime\ExecutionMode;

abstract class GuardedPersistenceBoundary
{
    public function __construct(private readonly PersistenceBoundaryGuard $guard) {}

    final protected function invoke(
        PersistenceOperation $expectedOperation,
        DomainNeutralPersistenceCommand $command,
        PersistenceBoundaryContext $context,
        callable $commit,
    ): PersistenceBoundaryResult {
        if ($command->operation !== $expectedOperation) {
            throw new PersistenceBoundaryException('FOUNDATION_PERSISTENCE_OPERATION_MISMATCH', 'A persistence command reached the wrong boundary.');
        }

        $authorized = $this->guard->authorize($command, $context);
        if ($authorized->mode === ExecutionMode::DryRun) {
            return new PersistenceBoundaryResult($expectedOperation, 'projected_no_write', 0, true);
        }

        $result = $commit($authorized);
        if (! $result instanceof PersistenceBoundaryResult || $result->operation !== $expectedOperation || $result->nonbinding) {
            throw new PersistenceBoundaryException('FOUNDATION_PERSISTENCE_RESULT_INVALID', 'A persistence boundary returned an incompatible result.');
        }
        if ($expectedOperation === PersistenceOperation::ExistingTargetLink && $result->businessDomainWrites !== 0) {
            throw new PersistenceBoundaryException('FOUNDATION_EXISTING_TARGET_MUTATION', 'An existing-target boundary reported a forbidden business-domain mutation.');
        }

        return $result;
    }
}
