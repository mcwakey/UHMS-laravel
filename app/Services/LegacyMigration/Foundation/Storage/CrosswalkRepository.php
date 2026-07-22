<?php

namespace App\Services\LegacyMigration\Foundation\Storage;

use App\Models\LegacyMigration\Crosswalk;
use App\Services\LegacyMigration\Foundation\Security\Phase3ProtectedStoreModelGuard;
use App\Services\LegacyMigration\Foundation\Security\ProtectedStoreAccessDeniedException;
use App\Services\LegacyMigration\Foundation\Security\ProtectedStoreOperationContext;
use App\Services\LegacyMigration\Foundation\Security\ProtectedToken as SecurityProtectedToken;
use Illuminate\Support\Facades\DB;

final class CrosswalkRepository
{
    public function __construct(private readonly ?ProtectedRecordSecurityRepository $security = null) {}

    /** @param array<string, mixed> $attributes */
    public function activate(array $attributes): Crosswalk
    {
        Phase3ProtectedStoreModelGuard::assertLegacyUnsealedMethodAllowed();

        return $this->activateInternal($attributes);
    }

    /** @param array<string, mixed> $attributes */
    private function activateInternal(array $attributes): Crosswalk
    {
        ProtectedToken::assert($attributes['protected_source_token'] ?? '', 'protected_source_token');
        ProtectedToken::assert($attributes['active_coordinate_token'] ?? '', 'active_coordinate_token');
        ProtectedToken::assert($attributes['idempotency_token'] ?? '', 'idempotency_token');
        ProtectedToken::nullable($attributes['protected_target_token'] ?? null, 'protected_target_token');

        $attributes['is_active'] = true;

        return Crosswalk::query()->create($attributes);
    }

    /** @param array<string, mixed> $attributes */
    public function activateProtected(ProtectedStoreOperationContext $context, array $attributes, array $tokenSet): Crosswalk
    {
        return DB::transaction(function () use ($context, $attributes, $tokenSet): Crosswalk {
            $attributes = ProtectedTokenSet::apply($attributes, $tokenSet);
            $encodedSourceToken = ProtectedTokenSet::primary($tokenSet, 'protected_source_token');
            $record = $this->activateInternal($attributes);
            $this->security()->seal(
                $context,
                $record,
                $encodedSourceToken,
                (string) $attributes['domain'],
                (int) $attributes['run_id'],
                (int) $attributes['source_snapshot_id'],
                isset($attributes['target_snapshot_id']) ? (int) $attributes['target_snapshot_id'] : null,
                (string) $attributes['access_classification'],
                (string) $attributes['retention_classification'],
                $tokenSet,
            );

            return $record;
        }, 3);
    }

    public function resolveActiveProtected(ProtectedStoreOperationContext $context, string $domain, string $encodedSourceToken, string $coordinateToken): ?ProtectedRecordProjection
    {
        $digest = SecurityProtectedToken::parse($encodedSourceToken)->lookupDigest();
        $id = DB::table('legacy_migration_crosswalks')
            ->where('domain', $domain)
            ->where('protected_source_token', $digest)
            ->where('active_coordinate_token', ProtectedToken::assert($coordinateToken, 'active_coordinate_token'))
            ->where('is_active', true)
            ->value('id');

        return $id === null ? null : $this->security()->readProjection($context, Crosswalk::class, (int) $id);
    }

    public function resolveActive(string $domain, string $sourceToken, string $coordinateToken): ?Crosswalk
    {
        Phase3ProtectedStoreModelGuard::assertLegacyUnsealedMethodAllowed();

        return Crosswalk::query()
            ->where('domain', $domain)
            ->where('protected_source_token', ProtectedToken::assert($sourceToken, 'protected_source_token'))
            ->where('active_coordinate_token', ProtectedToken::assert($coordinateToken, 'active_coordinate_token'))
            ->where('is_active', true)
            ->first();
    }

    public function revoke(Crosswalk $crosswalk, int $expectedVersion, string $updatedByToken, string $checksum): Crosswalk
    {
        Phase3ProtectedStoreModelGuard::assertLegacyMutationAllowed($crosswalk);
        ProtectedToken::assert($updatedByToken, 'updated_by_token');

        return DB::transaction(function () use ($crosswalk, $expectedVersion, $updatedByToken, $checksum): Crosswalk {
            $updated = Crosswalk::query()
                ->whereKey($crosswalk->getKey())
                ->where('is_active', true)
                ->where('lock_version', $expectedVersion)
                ->update([
                    'is_active' => false,
                    'active_coordinate_token' => null,
                    'state' => 'revoked',
                    'revocation_state' => 'revoked',
                    'integrity_checksum' => $checksum,
                    'updated_by_token' => $updatedByToken,
                    'lock_version' => $expectedVersion + 1,
                    'updated_at' => now(),
                ]);

            if ($updated !== 1) {
                throw new StorageIntegrityException('Crosswalk revocation failed because active lineage changed.');
            }

            return Crosswalk::query()->findOrFail($crosswalk->getKey());
        }, 3);
    }

    public function revokeProtected(
        ProtectedStoreOperationContext $context,
        int $crosswalkId,
        int $expectedVersion,
        string $encodedUpdatedByToken,
        string $checksum,
        string $actorDomain = 'migration_audit',
    ): ProtectedRecordProjection {
        $actor = SecurityProtectedToken::parse($encodedUpdatedByToken);
        if (! hash_equals($actorDomain, $actor->domain())) {
            throw ProtectedStoreAccessDeniedException::forCode('LM-SEC-STORE-TOKEN-SET-DIGEST-001');
        }

        return $this->security()->transition(
            $context,
            Crosswalk::class,
            $crosswalkId,
            fn ($record) => $this->revoke($record, $expectedVersion, $actor->lookupDigest(), $checksum),
            [
                'active_coordinate_token' => null,
                'updated_by_token' => ['encoded_token' => $encodedUpdatedByToken, 'domain' => $actorDomain],
            ],
        );
    }

    private function security(): ProtectedRecordSecurityRepository
    {
        return $this->security ?? throw ProtectedStoreAccessDeniedException::forCode('LM-SEC-STORE-REPOSITORY-BOUNDARY-MISSING-001');
    }
}
