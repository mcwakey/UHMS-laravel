<?php

namespace App\Services\LegacyMigration\Foundation\Storage;

use App\Models\LegacyMigration\QuarantineException;
use App\Models\LegacyMigration\QuarantineRoot;
use App\Services\LegacyMigration\Foundation\Security\Phase3ProtectedStoreModelGuard;
use App\Services\LegacyMigration\Foundation\Security\ProtectedStoreAccessDeniedException;
use App\Services\LegacyMigration\Foundation\Security\ProtectedStoreOperationContext;
use Illuminate\Support\Facades\DB;

final class QuarantineRepository
{
    public function __construct(
        private readonly QuarantineReleaseGuard $releaseGuard = new Phase3QuarantineReleaseGuard,
        private readonly ?ProtectedRecordSecurityRepository $security = null,
    ) {}

    /**
     * Creates one authoritative root and its required primary exception.
     *
     * @param  array<string, mixed>  $rootAttributes
     * @param  array<string, mixed>  $primaryExceptionAttributes
     */
    public function createRoot(array $rootAttributes, array $primaryExceptionAttributes): QuarantineRoot
    {
        Phase3ProtectedStoreModelGuard::assertLegacyUnsealedMethodAllowed();

        return $this->createRootInternal($rootAttributes, $primaryExceptionAttributes);
    }

    /** @param array<string, mixed> $rootAttributes @param array<string, mixed> $primaryExceptionAttributes */
    private function createRootInternal(array $rootAttributes, array $primaryExceptionAttributes): QuarantineRoot
    {
        ProtectedToken::assert($rootAttributes['root_token'] ?? '', 'root_token');
        ProtectedToken::assert($rootAttributes['chain_coordinate_token'] ?? '', 'chain_coordinate_token');

        return DB::transaction(function () use ($rootAttributes, $primaryExceptionAttributes): QuarantineRoot {
            $finalDisposition = (string) ($rootAttributes['current_disposition'] ?? 'held');
            $finalState = (string) ($rootAttributes['state'] ?? 'held');
            $root = QuarantineRoot::query()->create(array_replace($rootAttributes, [
                'current_disposition' => 'building',
                'state' => 'building',
            ]));
            $primaryExceptionAttributes['is_primary'] = true;
            $primaryExceptionAttributes['primary_guard_token'] = ProtectedToken::assert($rootAttributes['root_token'], 'root_token');
            $primaryExceptionAttributes['quarantine_root_id'] = $root->getKey();
            QuarantineException::query()->create($primaryExceptionAttributes);

            Phase3ProtectedStoreModelGuard::assertConnectionWriteAllowed($root->getConnectionName());
            $updated = QuarantineRoot::query()->whereKey($root->getKey())
                ->where('current_disposition', 'building')
                ->where('state', 'building')
                ->update([
                    'current_disposition' => $finalDisposition,
                    'state' => $finalState,
                    'updated_at' => now(),
                ]);
            if ($updated !== 1) {
                throw new StorageIntegrityException('Quarantine root could not be sealed with its primary exception.');
            }

            return $root->refresh();
        }, 3);
    }

    /** @param array<string, mixed> $attributes */
    public function appendSecondary(QuarantineRoot $root, array $attributes): QuarantineException
    {
        Phase3ProtectedStoreModelGuard::assertLegacyUnsealedMethodAllowed($root->getConnectionName());

        return $this->appendSecondaryInternal($root, $attributes);
    }

    /** @param array<string, mixed> $attributes */
    private function appendSecondaryInternal(QuarantineRoot $root, array $attributes): QuarantineException
    {
        $attributes['quarantine_root_id'] = $root->getKey();
        $attributes['is_primary'] = false;
        $attributes['primary_guard_token'] = null;

        return QuarantineException::query()->create($attributes);
    }

    /**
     * @param  array<string, mixed>  $rootAttributes
     * @param  array<string, mixed>  $primaryExceptionAttributes
     */
    public function createRootProtected(
        ProtectedStoreOperationContext $context,
        array $rootAttributes,
        array $primaryExceptionAttributes,
        array $rootTokenSet,
        array $primaryExceptionTokenSet,
    ): QuarantineRoot {
        return DB::transaction(function () use ($context, $rootAttributes, $primaryExceptionAttributes, $rootTokenSet, $primaryExceptionTokenSet): QuarantineRoot {
            $rootAttributes = ProtectedTokenSet::apply($rootAttributes, $rootTokenSet);
            $primaryExceptionTokenSet['primary_guard_token'] ??= $rootTokenSet['root_token'] ?? null;
            $primaryExceptionAttributes = ProtectedTokenSet::apply($primaryExceptionAttributes, $primaryExceptionTokenSet);
            $encodedRootToken = ProtectedTokenSet::primary($rootTokenSet, 'root_token');
            $encodedPrimaryExceptionToken = ProtectedTokenSet::primary($primaryExceptionTokenSet, 'idempotency_token');
            $root = $this->createRootInternal($rootAttributes, $primaryExceptionAttributes);
            /** @var QuarantineException $primary */
            $primary = QuarantineException::query()
                ->where('quarantine_root_id', $root->getKey())
                ->where('is_primary', true)
                ->firstOrFail();
            $domain = (string) $rootAttributes['root_domain'];
            foreach ([[$root, $encodedRootToken, $rootTokenSet], [$primary, $encodedPrimaryExceptionToken, $primaryExceptionTokenSet]] as [$record, $token, $tokenSet]) {
                $this->security()->seal(
                    $context,
                    $record,
                    $token,
                    $domain,
                    (int) $rootAttributes['run_id'],
                    (int) $rootAttributes['source_snapshot_id'],
                    null,
                    (string) $record->getRawOriginal('access_classification'),
                    (string) $record->getRawOriginal('retention_classification'),
                    $tokenSet,
                );
            }

            return $root;
        }, 3);
    }

    /** @param array<string, mixed> $attributes */
    public function appendSecondaryProtected(ProtectedStoreOperationContext $context, QuarantineRoot $root, array $attributes, array $tokenSet): QuarantineException
    {
        return DB::transaction(function () use ($context, $root, $attributes, $tokenSet): QuarantineException {
            $this->security()->readProjection($context, QuarantineRoot::class, (int) $root->getKey());
            $rootLineage = DB::table('legacy_migration_quarantine_roots')->where('id', $root->getKey())->first([
                'id', 'root_domain', 'run_id', 'source_snapshot_id', 'current_disposition',
            ]);
            if ($rootLineage === null || ! in_array((string) $rootLineage->current_disposition, ['held', 'review_pending', 'remediated_pending_recheck'], true)) {
                throw new StorageIntegrityException('Secondary exception requires a verified, active quarantine-root lineage.');
            }
            $attributes = ProtectedTokenSet::apply($attributes, $tokenSet);
            $encodedExceptionToken = ProtectedTokenSet::primary($tokenSet, 'idempotency_token');
            $exception = $this->appendSecondaryInternal($root, $attributes);
            $this->security()->seal(
                $context,
                $exception,
                $encodedExceptionToken,
                (string) $rootLineage->root_domain,
                (int) $rootLineage->run_id,
                (int) $rootLineage->source_snapshot_id,
                null,
                (string) $attributes['access_classification'],
                (string) $attributes['retention_classification'],
                $tokenSet,
            );

            return $exception;
        }, 3);
    }

    public function release(QuarantineRoot $root, int $expectedVersion, string $approvalToken, string $revalidationChecksum): QuarantineRoot
    {
        Phase3ProtectedStoreModelGuard::assertLegacyMutationAllowed($root);
        ProtectedToken::assert($approvalToken, 'release_approval_token');
        ProtectedToken::assert($revalidationChecksum, 'revalidation_checksum');
        $this->releaseGuard->assertReleaseAuthorized($root, $approvalToken, $revalidationChecksum);

        return DB::transaction(function () use ($root, $expectedVersion, $approvalToken, $revalidationChecksum): QuarantineRoot {
            $locked = QuarantineRoot::query()->lockForUpdate()->findOrFail($root->getKey());

            if ((int) $locked->lock_version !== $expectedVersion || ! in_array($locked->current_disposition, ['held', 'review_pending', 'remediated_pending_recheck'], true)) {
                throw new StorageIntegrityException('Quarantine release coordinate changed.');
            }

            if (QuarantineException::query()->where('quarantine_root_id', $locked->getKey())->where('is_primary', true)->count() !== 1) {
                throw new StorageIntegrityException('Quarantine release requires exactly one primary exception.');
            }

            $visited = [$locked->getKey() => true];
            $childRank = $locked->topological_rank;
            $parentId = $locked->parent_root_id;
            while ($parentId !== null) {
                if (isset($visited[$parentId])) {
                    throw new StorageIntegrityException('Quarantine dependency cycle detected.');
                }
                $visited[$parentId] = true;
                $parent = DB::table('legacy_migration_quarantine_roots')->where('id', $parentId)->lockForUpdate()->first([
                    'id', 'parent_root_id', 'topological_rank', 'current_disposition',
                ]);
                if ($parent === null || $parent->current_disposition !== 'released' || (int) $parent->topological_rank >= $childRank) {
                    throw new StorageIntegrityException('Quarantine descendants cannot release before a valid parent.');
                }
                $childRank = (int) $parent->topological_rank;
                $parentId = $parent->parent_root_id;
            }

            $locked->forceFill([
                'current_disposition' => 'released',
                'state' => 'released',
                'release_approval_token' => $approvalToken,
                'integrity_checksum' => $revalidationChecksum,
                'lock_version' => $expectedVersion + 1,
            ])->save();

            return $locked->refresh();
        }, 3);
    }

    public function releaseProtected(
        ProtectedStoreOperationContext $context,
        int $rootId,
        int $expectedVersion,
        string $encodedApprovalToken,
        string $revalidationChecksum,
        string $approvalDomain = 'migration_audit',
    ): ProtectedRecordProjection {
        $approval = \App\Services\LegacyMigration\Foundation\Security\ProtectedToken::parse($encodedApprovalToken);
        if (! hash_equals($approvalDomain, $approval->domain())) {
            throw ProtectedStoreAccessDeniedException::forCode('LM-SEC-STORE-TOKEN-SET-DIGEST-001');
        }

        return $this->security()->transition(
            $context,
            QuarantineRoot::class,
            $rootId,
            function ($record) use ($context, $expectedVersion, $approval, $revalidationChecksum): QuarantineRoot {
                $this->verifyParentLineage($context, $record);

                return $this->release($record, $expectedVersion, $approval->lookupDigest(), $revalidationChecksum);
            },
            ['release_approval_token' => ['encoded_token' => $encodedApprovalToken, 'domain' => $approvalDomain]],
        );
    }

    private function verifyParentLineage(ProtectedStoreOperationContext $context, QuarantineRoot $root): void
    {
        $visited = [(int) $root->getKey() => true];
        $childRank = (int) $root->getAttribute('topological_rank');
        $parentId = $root->getAttribute('parent_root_id');
        while ($parentId !== null) {
            $parentId = (int) $parentId;
            if (isset($visited[$parentId])) {
                throw new StorageIntegrityException('Quarantine dependency cycle detected.');
            }
            $visited[$parentId] = true;
            $parent = DB::table('legacy_migration_quarantine_roots')->where('id', $parentId)->first([
                'id', 'parent_root_id', 'topological_rank', 'current_disposition',
            ]);
            if ($parent === null || $parent->current_disposition !== 'released' || (int) $parent->topological_rank >= $childRank) {
                throw new StorageIntegrityException('Quarantine descendants cannot release before a valid parent.');
            }
            $this->security()->readProjection($context, QuarantineRoot::class, $parentId, [
                'parent_root_id' => $parent->parent_root_id,
                'topological_rank' => $parent->topological_rank,
                'current_disposition' => 'released',
            ]);
            $childRank = (int) $parent->topological_rank;
            $parentId = $parent->parent_root_id;
        }
    }

    private function security(): ProtectedRecordSecurityRepository
    {
        return $this->security ?? throw ProtectedStoreAccessDeniedException::forCode('LM-SEC-STORE-REPOSITORY-BOUNDARY-MISSING-001');
    }
}
