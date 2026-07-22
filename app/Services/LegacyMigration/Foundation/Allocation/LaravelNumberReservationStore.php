<?php

namespace App\Services\LegacyMigration\Foundation\Allocation;

use App\Models\LegacyMigration\NumberReservation;
use App\Services\LegacyMigration\Foundation\Storage\NumberReservationRepository;
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
        private readonly AllocationFaultInjector $faults = new NoopAllocationFaultInjector,
        private readonly ?NumberReservationRepository $protectedRepository = null,
        private readonly ?ReservationProtectionFactory $protection = null,
    ) {}

    public function find(AllocationRequest $request): ?ExistingAllocation
    {
        $this->assertConnectionComposition();
        $query = $this->db()->table('legacy_migration_number_reservations')
            ->join('legacy_migration_idempotency_records as idem', 'idem.id', '=', 'legacy_migration_number_reservations.idempotency_record_id')
            ->where('idem.domain', 'patient_core')
            ->where('idem.idempotency_token', $request->patientCoreKey);

        if ($this->protectedRepository !== null || $this->protection !== null) {
            if ($this->protectedRepository === null || $this->protection === null) {
                throw AllocationException::failClosed('PATIENT-NUM-PROTECTION-BOUNDARY-INCOMPLETE');
            }
            $id = $query->value('legacy_migration_number_reservations.id');
            if ($id === null) {
                return null;
            }
            $projection = $this->protectedRepository->verifyProtected(
                $this->protection->context($request),
                (int) $id,
                ['protected_source_token' => $this->protection->expectedProtectedSourceToken($request)],
            );
            $ordinal = (int) ($projection->fields['sequence_ordinal'] ?? 0);

            return new ExistingAllocation(
                $request->protectedSourceToken,
                $request->patientCoreKey,
                (string) ($projection->fields['configuration_fingerprint'] ?? ''),
                (string) ($projection->fields['period_key'] ?? ''),
                $request->configuration->format($ordinal),
                $ordinal,
                true,
            );
        }

        if ($this->db()->getDriverName() !== 'sqlite') {
            throw AllocationException::failClosed('PATIENT-NUM-PROTECTED-REPOSITORY-REQUIRED');
        }

        $row = $query->select([
            'legacy_migration_number_reservations.protected_source_token',
            'legacy_migration_number_reservations.configuration_fingerprint',
            'legacy_migration_number_reservations.period_key',
            'legacy_migration_number_reservations.sequence_ordinal',
        ])->first();

        return $row === null ? null : new ExistingAllocation(
            (string) $row->protected_source_token,
            $request->patientCoreKey,
            (string) $row->configuration_fingerprint,
            (string) $row->period_key,
            $request->configuration->format((int) $row->sequence_ordinal),
            (int) $row->sequence_ordinal,
            true,
        );
    }

    public function withLockedCoordinate(PinnedNumberingConfiguration $configuration, Closure $operation): mixed
    {
        $this->assertConnectionComposition();
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
        $this->assertConnectionComposition();
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

        $this->faults->inject(AllocationFaultPoint::BeforeReservationPersistence);

        $attributes = array_replace($metadata, $fixed);
        if ($this->protectedRepository !== null || $this->protection !== null) {
            if ($this->protectedRepository === null || $this->protection === null) {
                throw AllocationException::failClosed('PATIENT-NUM-PROTECTION-BOUNDARY-INCOMPLETE');
            }
            $this->protectedRepository->reserveProtected(
                $this->protection->context($request),
                $attributes,
                $this->protection->tokenSet($request, $number, $ordinal),
            );
        } else {
            NumberReservation::on($this->connectionName())->create($attributes);
        }

        $this->faults->inject(AllocationFaultPoint::AfterReservationInsertBeforeSequenceCas);

        $updated = $this->db()->table('patient_number_sequences')
            ->where('id', $this->lockedSequence->id)
            ->where('last_sequence', $ordinal - 1)
            ->update(['last_sequence' => $ordinal, 'updated_at' => now()]);
        if ($updated !== 1) {
            throw AllocationException::failClosed('PATIENT-NUM-SEQUENCE-CAS-CONFLICT');
        }

        $this->lockedSequence->last_sequence = $ordinal;

        $this->faults->inject(AllocationFaultPoint::AfterSequenceCasBeforeCommit);

        return new ExistingAllocation(
            $request->protectedSourceToken,
            $request->patientCoreKey,
            $request->configuration->fingerprint(),
            $request->configuration->periodKey,
            $number,
            $ordinal,
            true,
        );
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

    private function db(): ConnectionInterface
    {
        return DB::connection($this->connectionName());
    }

    public function connectionName(): string
    {
        $name = trim((string) ($this->connection ?? config('database.default')));
        if ($name === '') {
            throw AllocationException::failClosed('PATIENT-NUM-CONNECTION-BOUNDARY-MISSING');
        }

        return $name;
    }

    private function assertConnectionComposition(): void
    {
        if ($this->protectedRepository === null && $this->protection === null) {
            if ($this->db()->getDriverName() !== 'sqlite') {
                throw AllocationException::failClosed('PATIENT-NUM-PROTECTED-REPOSITORY-REQUIRED');
            }

            return;
        }
        if ($this->protectedRepository === null || $this->protection === null) {
            throw AllocationException::failClosed('PATIENT-NUM-PROTECTION-BOUNDARY-INCOMPLETE');
        }
        try {
            $this->protectedRepository->assertConnection($this->connectionName());
        } catch (\Throwable) {
            throw AllocationException::failClosed('PATIENT-NUM-CONNECTION-BOUNDARY-MISMATCH');
        }
        if (! hash_equals($this->connectionName(), $this->protection->connectionName())) {
            throw AllocationException::failClosed('PATIENT-NUM-CONNECTION-BOUNDARY-MISMATCH');
        }
    }
}
