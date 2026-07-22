<?php

namespace App\Services\LegacyMigration\Foundation\Storage;

use App\Models\LegacyMigration\Crosswalk;
use Illuminate\Support\Facades\DB;
use App\Services\LegacyMigration\Foundation\Security\Phase3ProtectedStoreModelGuard;

final class CrosswalkRepository
{
    /** @param array<string, mixed> $attributes */
    public function activate(array $attributes): Crosswalk
    {
        ProtectedToken::assert($attributes['protected_source_token'] ?? '', 'protected_source_token');
        ProtectedToken::assert($attributes['active_coordinate_token'] ?? '', 'active_coordinate_token');
        ProtectedToken::assert($attributes['idempotency_token'] ?? '', 'idempotency_token');
        ProtectedToken::nullable($attributes['protected_target_token'] ?? null, 'protected_target_token');

        $attributes['is_active'] = true;

        return Crosswalk::query()->create($attributes);
    }

    public function resolveActive(string $domain, string $sourceToken, string $coordinateToken): ?Crosswalk
    {
        return Crosswalk::query()
            ->where('domain', $domain)
            ->where('protected_source_token', ProtectedToken::assert($sourceToken, 'protected_source_token'))
            ->where('active_coordinate_token', ProtectedToken::assert($coordinateToken, 'active_coordinate_token'))
            ->where('is_active', true)
            ->first();
    }

    public function revoke(Crosswalk $crosswalk, int $expectedVersion, string $updatedByToken, string $checksum): Crosswalk
    {
        Phase3ProtectedStoreModelGuard::assertConnectionWriteAllowed($crosswalk->getConnectionName());
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
}
