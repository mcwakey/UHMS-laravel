<?php

namespace App\Services\LegacyMigration\Foundation\Allocation;

use App\Models\LegacyMigration\NumberReservation;
use Closure;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\Query\Builder;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

/**
 * Uses the operational sequence row but writes only the protected foundation
 * reservation. Patient and other business rows are deliberately unreachable.
 */
final class LaravelNumberReservationStore implements NumberReservationStore
{
    private ?object $lockedSequence = null;

    public function __construct(
        private readonly ReservationAttributeFactory $attributes,
        private readonly ?string $connection = null,
        private readonly FoundationAllocationWriteGuard $writeGuard = new Phase3AllocationWriteGuard,
    ) {}

    public function findByPatientCoreKey(string $patientCoreKey): ?ExistingAllocation
    {
        $model = NumberReservation::on($this->connectionName())
            ->join('legacy_migration_idempotency_records as idem', 'idem.id', '=', 'legacy_migration_number_reservations.idempotency_record_id')
            ->where('idem.domain', 'patient_core')
            ->where('idem.idempotency_token', $patientCoreKey)
            ->select('legacy_migration_number_reservations.*')
            ->first();

        return $model === null ? null : $this->toExisting($model, $patientCoreKey);
    }

    public function withLockedCoordinate(PinnedNumberingConfiguration $configuration, Closure $operation): mixed
    {
        $this->writeGuard->assertReservationWriteAllowed($this->connectionName());

        if ($this->lockedSequence !== null) {
            throw AllocationException::failClosed('PATIENT-NUM-NESTED-SEQUENCE-LOCK');
        }

        return $this->db()->transaction(function () use ($configuration, $operation): mixed {
            $this->assertTablesAvailable();
            $this->createCoordinateIfMissing($configuration);

            $row = $this->coordinateQuery($configuration)->lockForUpdate()->first();
            if ($row === null) {
                throw AllocationException::failClosed('PATIENT-NUM-MISSING-SEQUENCE-ROW');
            }

            $this->lockedSequence = $row;
            try {
                return $operation();
            } finally {
                $this->lockedSequence = null;
            }
        }, 3);
    }

    public function nextOrdinal(PinnedNumberingConfiguration $configuration): int
    {
        $row = $this->lockedSequence;
        if ($row === null
            || (string) $row->prefix !== $configuration->prefix
            || (string) $row->period_type !== $configuration->resetPeriod->value
            || (string) $row->period_key !== $configuration->periodKey) {
            throw AllocationException::failClosed('PATIENT-NUM-SEQUENCE-NOT-LOCKED');
        }

        $next = (int) $row->last_sequence + 1;
        if ($next < 1 || strlen((string) $next) > $configuration->sequenceWidth) {
            throw AllocationException::failClosed('PATIENT-NUM-SEQUENCE-OVERFLOW');
        }

        return $next;
    }

    public function persist(AllocationRequest $request, string $number, int $ordinal): ExistingAllocation
    {
        $next = $this->nextOrdinal($request->configuration);
        if ($ordinal !== $next || $number !== $request->configuration->format($ordinal)) {
            throw AllocationException::failClosed('PATIENT-NUM-NONDETERMINISTIC-RESERVATION');
        }

        $metadata = $this->attributes->make($request, $number, $ordinal);
        $fixed = [
            'domain' => 'patient_number',
            'protected_source_token' => $request->protectedSourceToken,
            'numbering_coordinate_token' => $request->configuration->coordinateToken(),
            'sequence_ordinal' => $ordinal,
            'configuration_fingerprint' => $request->configuration->fingerprint(),
            'period_key' => $request->configuration->periodKey,
            'timezone' => $request->configuration->timezone,
            'state' => 'reserved',
            'consumption_classification' => 'explained',
            'encrypted_number_payload' => ['number' => $number],
            'encrypted_explanation' => ['code' => 'PILOT-RESUME-002', 'status' => 'pending_atomic_unit'],
            'created_by_token' => null,
            'updated_by_token' => null,
        ];

        /** @var NumberReservation $reservation */
        $reservation = NumberReservation::on($this->connectionName())->create(array_replace($metadata, $fixed));

        $updated = $this->db()->table('patient_number_sequences')
            ->where('id', $this->lockedSequence->id)
            ->where('last_sequence', $ordinal - 1)
            ->update(['last_sequence' => $ordinal, 'updated_at' => now()]);
        if ($updated !== 1) {
            throw AllocationException::failClosed('PATIENT-NUM-SEQUENCE-CAS-CONFLICT');
        }

        $this->lockedSequence->last_sequence = $ordinal;

        return $this->toExisting($reservation, $request->patientCoreKey);
    }

    private function createCoordinateIfMissing(PinnedNumberingConfiguration $configuration): void
    {
        try {
            $this->db()->table('patient_number_sequences')->insertOrIgnore([
                'prefix' => $configuration->prefix,
                'period_type' => $configuration->resetPeriod->value,
                'period_key' => $configuration->periodKey,
                'last_sequence' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } catch (QueryException) {
            // A concurrent creator may win the unique coordinate. Re-query and
            // lock below; any other failure remains fail-closed when absent.
        }
    }

    private function coordinateQuery(PinnedNumberingConfiguration $configuration): Builder
    {
        return $this->db()->table('patient_number_sequences')
            ->where('prefix', $configuration->prefix)
            ->where('period_type', $configuration->resetPeriod->value)
            ->where('period_key', $configuration->periodKey);
    }

    private function assertTablesAvailable(): void
    {
        $schema = $this->db()->getSchemaBuilder();
        foreach (['patient_number_sequences', 'legacy_migration_number_reservations', 'legacy_migration_idempotency_records'] as $table) {
            if (! $schema->hasTable($table)) {
                throw AllocationException::failClosed('PATIENT-NUM-REQUIRED-TABLE-UNAVAILABLE');
            }
        }
    }

    private function toExisting(NumberReservation $reservation, string $patientCoreKey): ExistingAllocation
    {
        $payload = $reservation->encrypted_number_payload;
        if (! is_array($payload) || ! is_string($payload['number'] ?? null) || $payload['number'] === '') {
            throw AllocationException::failClosed('PATIENT-NUM-INVALID-RESERVATION');
        }

        return new ExistingAllocation(
            (string) $reservation->protected_source_token,
            $patientCoreKey,
            (string) $reservation->configuration_fingerprint,
            (string) $reservation->period_key,
            $payload['number'],
            (int) $reservation->sequence_ordinal,
            true,
        );
    }

    private function db(): ConnectionInterface
    {
        return DB::connection($this->connectionName());
    }

    private function connectionName(): string
    {
        return $this->connection ?? (string) config('database.default');
    }
}
