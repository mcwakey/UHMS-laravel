<?php

namespace App\Services\LegacyMigration\Foundation\Storage;

use App\Models\LegacyMigration\QuarantineException;
use App\Models\LegacyMigration\QuarantineRoot;
use Illuminate\Support\Facades\DB;
use App\Services\LegacyMigration\Foundation\Security\Phase3ProtectedStoreModelGuard;

final class QuarantineRepository
{
    public function __construct(
        private readonly QuarantineReleaseGuard $releaseGuard = new Phase3QuarantineReleaseGuard,
    ) {}

    /**
     * Creates one authoritative root and its required primary exception.
     *
     * @param  array<string, mixed>  $rootAttributes
     * @param  array<string, mixed>  $primaryExceptionAttributes
     */
    public function createRoot(array $rootAttributes, array $primaryExceptionAttributes): QuarantineRoot
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
            $primaryExceptionAttributes['primary_guard_token'] = $root->root_token;
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
        $attributes['quarantine_root_id'] = $root->getKey();
        $attributes['is_primary'] = false;
        $attributes['primary_guard_token'] = null;

        return QuarantineException::query()->create($attributes);
    }

    public function release(QuarantineRoot $root, int $expectedVersion, string $approvalToken, string $revalidationChecksum): QuarantineRoot
    {
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
                $parent = QuarantineRoot::query()->lockForUpdate()->findOrFail($parentId);
                if ($parent->current_disposition !== 'released' || $parent->topological_rank >= $childRank) {
                    throw new StorageIntegrityException('Quarantine descendants cannot release before a valid parent.');
                }
                $childRank = $parent->topological_rank;
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
}
