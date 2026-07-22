<?php

namespace App\Services\LegacyMigration\Foundation\Storage;

use App\Models\LegacyMigration\NumberReservation;
use Illuminate\Support\Facades\DB;
use App\Services\LegacyMigration\Foundation\Security\Phase3ProtectedStoreModelGuard;

final class NumberReservationRepository
{
    /** @param array<string, mixed> $attributes */
    public function reserve(array $attributes): NumberReservation
    {
        ProtectedToken::assert($attributes['protected_source_token'] ?? '', 'protected_source_token');
        ProtectedToken::assert($attributes['numbering_coordinate_token'] ?? '', 'numbering_coordinate_token');
        ProtectedToken::assert($attributes['protected_number_token'] ?? '', 'protected_number_token');

        return DB::transaction(function () use ($attributes): NumberReservation {
            $existing = NumberReservation::query()
                ->where('idempotency_record_id', $attributes['idempotency_record_id'])
                ->lockForUpdate()
                ->first();

            if ($existing !== null) {
                foreach (['run_id', 'protected_source_token', 'numbering_coordinate_token', 'configuration_fingerprint', 'period_key'] as $field) {
                    if ((string) $existing->{$field} !== (string) $attributes[$field]) {
                        throw new StorageIntegrityException('Number reservation lineage conflicts; replacement allocation is prohibited.');
                    }
                }

                return $existing;
            }

            return NumberReservation::query()->create($attributes);
        }, 3);
    }

    public function classifyConsumption(NumberReservation $reservation, int $expectedVersion, string $classification, string $state, string $checksum): NumberReservation
    {
        Phase3ProtectedStoreModelGuard::assertConnectionWriteAllowed($reservation->getConnectionName());
        if (! in_array($classification, ['committed', 'transactionally_released', 'explained'], true)) {
            throw new StorageIntegrityException('Every sequence consumption needs an approved classification.');
        }

        $updated = NumberReservation::query()
            ->whereKey($reservation->getKey())
            ->where('lock_version', $expectedVersion)
            ->update([
                'consumption_classification' => $classification,
                'state' => $state,
                'integrity_checksum' => $checksum,
                'lock_version' => $expectedVersion + 1,
                'updated_at' => now(),
            ]);

        if ($updated !== 1) {
            throw new StorageIntegrityException('Number consumption compare-and-set failed.');
        }

        return NumberReservation::query()->findOrFail($reservation->getKey());
    }
}
