<?php

namespace App\Services\LegacyMigration\Foundation\Storage;

use App\Models\LegacyMigration\ContractBundle;
use App\Models\LegacyMigration\MigrationRun;

final class RunManifestRepository
{
    public function __construct(private readonly CompareAndSet $compareAndSet = new CompareAndSet) {}

    /** @param array<string, mixed> $attributes */
    public function appendContractBundle(array $attributes): ContractBundle
    {
        ProtectedToken::assert($attributes['bundle_token'] ?? '', 'bundle_token');

        return ContractBundle::query()->create($attributes);
    }

    /** @param array<string, mixed> $attributes */
    public function createRun(array $attributes): MigrationRun
    {
        ProtectedToken::assert($attributes['run_token'] ?? '', 'run_token');
        ProtectedToken::assert($attributes['cohort_token'] ?? '', 'cohort_token');

        if (! in_array($attributes['state'] ?? 'NOT_STARTED', MigrationRun::STATES, true)) {
            throw new StorageIntegrityException('Unknown Phase 2F run state.');
        }

        return MigrationRun::query()->create($attributes);
    }

    public function findByToken(string $runToken): ?MigrationRun
    {
        return MigrationRun::query()->where('run_token', ProtectedToken::assert($runToken, 'run_token'))->first();
    }

    /** @param array<string, mixed> $metadata */
    public function advance(MigrationRun $run, string $expectedState, int $expectedVersion, string $nextState, array $metadata = []): MigrationRun
    {
        /** @var MigrationRun $advanced */
        $advanced = $this->compareAndSet->state($run, $expectedState, $expectedVersion, $nextState, $metadata);

        return $advanced;
    }
}
