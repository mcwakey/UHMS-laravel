<?php

namespace App\Services\LegacyMigration\Foundation\Storage;

use App\Models\LegacyMigration\ContractBundle;
use App\Models\LegacyMigration\MigrationRun;
use App\Services\LegacyMigration\Foundation\Security\Phase3ProtectedStoreModelGuard;
use App\Services\LegacyMigration\Foundation\Security\ProtectedStoreAccessDeniedException;
use App\Services\LegacyMigration\Foundation\Security\ProtectedStoreOperationContext;
use App\Services\LegacyMigration\Foundation\Security\ProtectedToken as SecurityProtectedToken;
use Illuminate\Support\Facades\DB;

final class RunManifestRepository
{
    public function __construct(
        private readonly CompareAndSet $compareAndSet = new CompareAndSet,
        private readonly ?ProtectedRecordSecurityRepository $security = null,
    ) {}

    /** @param array<string, mixed> $attributes */
    public function appendContractBundle(array $attributes): ContractBundle
    {
        Phase3ProtectedStoreModelGuard::assertLegacyUnsealedMethodAllowed();

        return $this->appendContractBundleInternal($attributes);
    }

    /** @param array<string, mixed> $attributes */
    private function appendContractBundleInternal(array $attributes): ContractBundle
    {
        ProtectedToken::assert($attributes['bundle_token'] ?? '', 'bundle_token');

        return ContractBundle::query()->create($attributes);
    }

    /** @param array<string,mixed> $attributes @param array<string,array{encoded_token:string,domain:string}> $tokenSet */
    public function appendContractBundleProtected(ProtectedStoreOperationContext $context, array $attributes, array $tokenSet): ContractBundle
    {
        return DB::transaction(function () use ($context, $attributes, $tokenSet): ContractBundle {
            $attributes = ProtectedTokenSet::apply($attributes, $tokenSet);
            $primary = ProtectedTokenSet::primary($tokenSet, 'bundle_token');
            $record = $this->appendContractBundleInternal($attributes);
            $this->security()->seal($context, $record, $primary, ProtectedTokenSet::domain($tokenSet, 'bundle_token'), null, null, null, (string) $attributes['access_classification'], (string) $attributes['retention_classification'], $tokenSet);

            return $record;
        }, 3);
    }

    public function resolveContractBundleProtected(
        ProtectedStoreOperationContext $context,
        string $bundleVersion,
        string $bundleHash,
    ): ?ProtectedRecordProjection {
        if ($bundleVersion === '' || preg_match('/\A[a-f0-9]{64}\z/D', $bundleHash) !== 1) {
            throw new StorageIntegrityException('Contract-bundle reuse requires an exact version and SHA-256 hash.');
        }
        $rows = DB::table('legacy_migration_contract_bundles')
            ->where('bundle_version', $bundleVersion)
            ->get(['id', 'bundle_hash']);
        if ($rows->isEmpty()) {
            return null;
        }
        $compatible = $rows->first(static fn (object $row): bool => hash_equals($bundleHash, (string) $row->bundle_hash));
        if ($compatible === null || $rows->count() !== 1) {
            throw new StorageIntegrityException('Contract-bundle version already exists with incompatible lineage.');
        }

        return $this->security()->readProjection(
            $context,
            ContractBundle::class,
            (int) $compatible->id,
            ['bundle_version' => $bundleVersion, 'bundle_hash' => $bundleHash],
        );
    }

    /** @param array<string, mixed> $attributes */
    public function createRun(array $attributes): MigrationRun
    {
        Phase3ProtectedStoreModelGuard::assertLegacyUnsealedMethodAllowed();

        return $this->createRunInternal($attributes);
    }

    /** @param array<string, mixed> $attributes */
    private function createRunInternal(array $attributes): MigrationRun
    {
        ProtectedToken::assert($attributes['run_token'] ?? '', 'run_token');
        ProtectedToken::assert($attributes['cohort_token'] ?? '', 'cohort_token');

        if (! in_array($attributes['state'] ?? 'NOT_STARTED', MigrationRun::STATES, true)) {
            throw new StorageIntegrityException('Unknown Phase 2F run state.');
        }

        return MigrationRun::query()->create($attributes);
    }

    /** @param array<string,mixed> $attributes @param array<string,array{encoded_token:string,domain:string}> $tokenSet */
    public function createRunProtected(ProtectedStoreOperationContext $context, array $attributes, array $tokenSet): MigrationRun
    {
        return DB::transaction(function () use ($context, $attributes, $tokenSet): MigrationRun {
            $attributes = ProtectedTokenSet::apply($attributes, $tokenSet);
            $primary = ProtectedTokenSet::primary($tokenSet, 'run_token');
            $record = $this->createRunInternal($attributes);
            $this->security()->seal($context, $record, $primary, ProtectedTokenSet::domain($tokenSet, 'run_token'), (int) $record->id, null, null, (string) $attributes['access_classification'], (string) $attributes['retention_classification'], $tokenSet);

            return $record;
        }, 3);
    }

    public function findByToken(string $runToken): ?MigrationRun
    {
        Phase3ProtectedStoreModelGuard::assertLegacyUnsealedMethodAllowed();

        return MigrationRun::query()->where('run_token', ProtectedToken::assert($runToken, 'run_token'))->first();
    }

    public function findByTokenProtected(ProtectedStoreOperationContext $context, string $encodedRunToken): ?ProtectedRecordProjection
    {
        $id = DB::table('legacy_migration_runs')->where('run_token', SecurityProtectedToken::parse($encodedRunToken)->lookupDigest())->value('id');

        return $id === null ? null : $this->security()->readProjection($context, MigrationRun::class, (int) $id);
    }

    /** @param array<string, mixed> $metadata */
    public function advance(MigrationRun $run, string $expectedState, int $expectedVersion, string $nextState, array $metadata = []): MigrationRun
    {
        Phase3ProtectedStoreModelGuard::assertLegacyMutationAllowed($run);
        /** @var MigrationRun $advanced */
        $advanced = $this->compareAndSet->state($run, $expectedState, $expectedVersion, $nextState, $metadata);

        return $advanced;
    }

    /** @param array<string,mixed> $metadata */
    public function advanceProtected(ProtectedStoreOperationContext $context, int $runId, string $expectedState, int $expectedVersion, string $nextState, array $metadata = []): ProtectedRecordProjection
    {
        return $this->security()->transition(
            $context,
            MigrationRun::class,
            $runId,
            fn ($record) => $this->advance($record, $expectedState, $expectedVersion, $nextState, $metadata),
        );
    }

    private function security(): ProtectedRecordSecurityRepository
    {
        return $this->security ?? throw ProtectedStoreAccessDeniedException::forCode('LM-SEC-STORE-REPOSITORY-BOUNDARY-MISSING-001');
    }
}
