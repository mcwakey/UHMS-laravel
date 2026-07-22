<?php

namespace App\Services\LegacyMigration\Foundation\Storage;

use App\Models\LegacyMigration\NumberReservation;
use App\Models\LegacyMigration\IdempotencyRecord;
use App\Services\LegacyMigration\Foundation\Allocation\ReservationCoordinateContextFactory;
use App\Services\LegacyMigration\Foundation\Security\Phase3ProtectedStoreModelGuard;
use App\Services\LegacyMigration\Foundation\Security\ProtectedStoreAccessDeniedException;
use App\Services\LegacyMigration\Foundation\Security\ProtectedStoreAccessSession;
use App\Services\LegacyMigration\Foundation\Security\ProtectedStoreOperationContext;
use Illuminate\Support\Facades\DB;

final class NumberReservationRepository
{
    public function __construct(
        private readonly ?ProtectedRecordSecurityRepository $security = null,
        private readonly ?ReservationCoordinateContextFactory $coordinateContexts = null,
        private readonly ?string $connection = null,
    ) {}

    public function connectionName(): string
    {
        $name = trim((string) ($this->connection ?? config('database.default')));
        if ($name === '') {
            throw ProtectedStoreAccessDeniedException::forCode('LM-SEC-RESERVATION-CONNECTION-MISSING-001');
        }

        return $name;
    }

    public function assertConnection(string $expected): void
    {
        if (! hash_equals(trim($expected), $this->connectionName())) {
            throw ProtectedStoreAccessDeniedException::forCode('LM-SEC-RESERVATION-CONNECTION-MISMATCH-001');
        }
        try {
            $this->security()->assertConnection($this->connectionName());
        } catch (\Throwable) {
            throw ProtectedStoreAccessDeniedException::forCode('LM-SEC-RESERVATION-CONNECTION-MISMATCH-001');
        }
    }

    /** @param array<string, mixed> $attributes */
    public function reserve(array $attributes): NumberReservation
    {
        Phase3ProtectedStoreModelGuard::assertLegacyUnsealedMethodAllowed();

        return $this->reserveInternal($attributes);
    }

    /** @param array<string, mixed> $attributes */
    private function reserveInternal(array $attributes): NumberReservation
    {
        ProtectedToken::assert($attributes['protected_source_token'] ?? '', 'protected_source_token');
        ProtectedToken::assert($attributes['numbering_coordinate_token'] ?? '', 'numbering_coordinate_token');
        ProtectedToken::assert($attributes['protected_number_token'] ?? '', 'protected_number_token');

        return DB::connection($this->connectionName())->transaction(function () use ($attributes): NumberReservation {
            $existing = NumberReservation::on($this->connectionName())
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

            // The test/runtime process can inspect multiple disposable schemas;
            // avoid Laravel's process-static guardable-column cache silently
            // dropping a coordinate after a schema switch.
            $reservation = new NumberReservation;
            $reservation->setConnection($this->connectionName());
            $reservation->forceFill($attributes);
            $reservation->save();

            return $reservation;
        }, 3);
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @param  array<string, array{encoded_token:string, domain:string}>  $tokenSet
     */
    public function reserveProtected(
        ProtectedStoreOperationContext $context,
        array $attributes,
        array $tokenSet,
    ): ProtectedRecordProjection {
        $this->assertConnection($this->connectionName());

        return ProtectedStoreAccessSession::run($context, function () use ($context, $attributes, $tokenSet): ProtectedRecordProjection {
            $attributes = ProtectedTokenSet::apply($attributes, $tokenSet);
            $idempotency = DB::connection($this->connectionName())->table('legacy_migration_idempotency_records')
                ->where('id', $attributes['idempotency_record_id'])
                ->first(['run_id', 'source_snapshot_id', 'target_snapshot_id', 'domain']);
            if ($idempotency === null
                || (string) $idempotency->domain !== 'patient_core'
                || (int) $idempotency->run_id !== (int) $attributes['run_id']) {
                throw new StorageIntegrityException('Number reservation idempotency lineage is missing or incompatible.');
            }
            $idempotencyContext = $this->coordinateContexts()->idempotencyContext(
                (int) $attributes['idempotency_record_id'],
                (int) $idempotency->run_id,
                (int) $idempotency->source_snapshot_id,
                (int) $idempotency->target_snapshot_id,
            );
            $verifiedIdempotency = $this->security()->readProjection(
                $idempotencyContext,
                IdempotencyRecord::class,
                (int) $attributes['idempotency_record_id'],
                ['run_id' => $attributes['run_id'], 'domain' => 'patient_core'],
            );
            foreach (['run_id', 'source_snapshot_id', 'target_snapshot_id', 'domain'] as $field) {
                if ((string) ($verifiedIdempotency->fields[$field] ?? '') !== (string) $idempotency->{$field}) {
                    throw new StorageIntegrityException('Number reservation idempotency envelope conflicts with its coordinate.');
                }
            }
            $context->assertCoordinates(
                (int) $idempotency->run_id,
                (int) $idempotency->source_snapshot_id,
                (int) $idempotency->target_snapshot_id,
            );

            return DB::connection($this->connectionName())->transaction(function () use ($context, $attributes, $tokenSet, $idempotency): ProtectedRecordProjection {
                $existingId = DB::connection($this->connectionName())->table('legacy_migration_number_reservations')
                    ->where('idempotency_record_id', $attributes['idempotency_record_id'])
                    ->lockForUpdate()
                    ->value('id');
                $expected = [];
                foreach (['run_id', 'protected_source_token', 'numbering_coordinate_token', 'configuration_fingerprint', 'period_key'] as $field) {
                    $expected[$field] = $attributes[$field];
                }

                if ($existingId !== null) {
                    return $this->security()->readProjection($context, NumberReservation::class, (int) $existingId, $expected);
                }

                $record = $this->reserveInternal($attributes);
                $primary = ProtectedTokenSet::primary($tokenSet, 'protected_source_token');
                $this->security()->seal(
                    $context,
                    $record,
                    $primary,
                    ProtectedTokenSet::domain($tokenSet, 'protected_source_token'),
                    (int) $attributes['run_id'],
                    (int) $idempotency->source_snapshot_id,
                    (int) $idempotency->target_snapshot_id,
                    (string) $attributes['access_classification'],
                    (string) $attributes['retention_classification'],
                    $tokenSet,
                );

                return $this->security()->readProjection($context, NumberReservation::class, (int) $record->id, $expected);
            }, 3);
        });
    }

    public function classifyConsumption(NumberReservation $reservation, int $expectedVersion, string $classification, string $state, string $checksum): NumberReservation
    {
        Phase3ProtectedStoreModelGuard::assertLegacyMutationAllowed($reservation);
        if (! in_array($classification, ['committed', 'transactionally_released', 'explained'], true)) {
            throw new StorageIntegrityException('Every sequence consumption needs an approved classification.');
        }

        $updated = NumberReservation::on($this->connectionName())
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

        return NumberReservation::on($this->connectionName())->findOrFail($reservation->getKey());
    }

    public function classifyConsumptionProtected(
        ProtectedStoreOperationContext $context,
        int $reservationId,
        int $expectedVersion,
        string $classification,
        string $state,
        string $checksum,
    ): ProtectedRecordProjection {
        $this->assertConnection($this->connectionName());

        return $this->security()->transition(
            $context,
            NumberReservation::class,
            $reservationId,
            fn ($record) => $this->classifyConsumption($record, $expectedVersion, $classification, $state, $checksum),
        );
    }

    /** @param array<string,mixed> $expected */
    public function verifyProtected(
        ProtectedStoreOperationContext $context,
        int $reservationId,
        array $expected = [],
    ): ProtectedRecordProjection {
        $this->assertConnection($this->connectionName());

        return $this->security()->readProjection($context, NumberReservation::class, $reservationId, $expected);
    }

    private function security(): ProtectedRecordSecurityRepository
    {
        return $this->security ?? throw ProtectedStoreAccessDeniedException::forCode('LM-SEC-STORE-REPOSITORY-BOUNDARY-MISSING-001');
    }

    private function coordinateContexts(): ReservationCoordinateContextFactory
    {
        return $this->coordinateContexts
            ?? throw ProtectedStoreAccessDeniedException::forCode('LM-SEC-RESERVATION-COORDINATE-BOUNDARY-MISSING-001');
    }
}
