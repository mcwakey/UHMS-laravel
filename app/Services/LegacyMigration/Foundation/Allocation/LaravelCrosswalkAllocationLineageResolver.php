<?php

namespace App\Services\LegacyMigration\Foundation\Allocation;

use Illuminate\Support\Facades\DB;

final readonly class LaravelCrosswalkAllocationLineageResolver implements AllocationLineageResolver
{
    public function __construct(
        private NumberReservationStore $reservations,
        private string $domain = 'patient',
        private ?string $connection = null,
    ) {}

    public function resolveSuccessful(AllocationRequest $request): ?ExistingAllocation
    {
        $connection = DB::connection($this->connection ?? (string) config('database.default'));
        if (! $connection->getSchemaBuilder()->hasTable('legacy_migration_crosswalks')) {
            throw AllocationException::failClosed('PATIENT-NUM-CROSSWALK-UNAVAILABLE');
        }

        $mappings = $connection->table('legacy_migration_crosswalks')
            ->where('domain', $this->domain)
            ->where('protected_source_token', $request->protectedSourceToken)
            ->where('is_active', true)
            ->limit(2)
            ->get(['idempotency_token']);

        if ($mappings->count() > 1) {
            throw AllocationException::failClosed('PATIENT-NUM-CROSSWALK-CARDINALITY');
        }
        if ($mappings->isEmpty()) {
            return null;
        }
        if (! hash_equals((string) $mappings->first()->idempotency_token, $request->patientCoreKey)) {
            throw AllocationException::failClosed('LEGACY-PATIENT-NUMBER-038');
        }

        $allocation = $this->reservations->findByPatientCoreKey($request->patientCoreKey);
        if ($allocation === null) {
            throw AllocationException::failClosed('PATIENT-NUM-CROSSWALK-WITHOUT-ALLOCATION');
        }

        return $allocation;
    }
}
